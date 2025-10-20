<?php

namespace Tests\Feature\Otp;

use App\Models\Enum\OtpPurpose;
use App\Models\OtpToken;
use App\Services\Otp\OtpCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class VerifyOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Date::setTestNow(now());
        Config::set('otp.pepper', 'test-pepper');
        Config::set('otp.length', 6);
    }

    public function test_verify_register_flow_returns_jwt_and_verification_next_step(): void
    {
        $phone = '+989145509000';
        $code = '123456';
        $ip = '203.0.113.77';
        $deviceId = 'dev-reg-777';

        $otp = OtpToken::create([
            'phone' => $phone,
            'code_hash' => $code, // mutator hashes using pepper + random salt
            'purpose' => OtpPurpose::REGISTER,
            'expires_at' => now()->addMinutes(5),
            'request_ip' => $ip,
            'device_id' => $deviceId,
        ]);
        OtpCache::set($otp);

        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/v1/otp/verify', [
                'phone' => $phone,
                'code' => $code,
            ], [
                'X-Device-Id' => $deviceId,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'next_step',
                'token' => ['access_token', 'token_type', 'expires_in'],
                'message',
            ]);

        $this->assertSame('verification', $response->json('next_step'));
        $this->assertSame('bearer', $response->json('token.token_type'));
        $this->assertIsInt($response->json('token.expires_in'));
        $this->assertSame(trans('otp.loggedin'), $response->json('message'));

        $otp->refresh();
        $this->assertNotNull($otp->consumed_at);

        $this->assertNull(OtpCache::get($phone));
    }

    public function test_verify_login_flow_returns_jwt_and_panel_next_step(): void
    {
        $phone = '+989145509000';
        $code = '654321';
        $ip = '198.51.100.77';
        $deviceId = 'dev-log-888';

        $otp = OtpToken::create([
            'phone' => $phone,
            'code_hash' => $code,
            'purpose' => OtpPurpose::LOGIN,
            'expires_at' => now()->addMinutes(5),
            'request_ip' => $ip,
            'device_id' => $deviceId,
        ]);
        OtpCache::set($otp);

        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/v1/otp/verify', [
                'phone' => $phone,
                'code' => $code,
            ], [
                'X-Device-Id' => $deviceId,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'next_step',
                'token' => ['access_token', 'token_type', 'expires_in'],
                'message',
            ]);

        $this->assertSame('panel', $response->json('next_step'));
        $this->assertSame('bearer', $response->json('token.token_type'));
        $this->assertIsInt($response->json('token.expires_in'));
        $this->assertSame(trans('otp.loggedin'), $response->json('message'));

        $otp->refresh();
        $this->assertNotNull($otp->consumed_at);

        $this->assertNull(OtpCache::get($phone));
    }

    public function test_verify_validation_rejects_missing_phone(): void
    {
        Config::set('otp.length', 6);

        $response = $this->postJson('/api/v1/otp/verify', [
            'code' => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_verify_validation_rejects_invalid_phone(): void
    {
        Config::set('otp.length', 6);

        $response = $this->postJson('/api/v1/otp/verify', [
            'phone' => 'not-a-valid-phone',
            'code' => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_verify_validation_rejects_missing_code(): void
    {
        $response = $this->postJson('/api/v1/otp/verify', [
            'phone' => '+989145509000',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_verify_validation_rejects_invalid_code(): void
    {
        Config::set('otp.length', 6);

        // Too short
        $responseShort = $this->postJson('/api/v1/otp/verify', [
            'phone' => '+989145509000',
            'code' => '123',
        ]);
        $responseShort->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        // Non-digit
        $responseAlpha = $this->postJson('/api/v1/otp/verify', [
            'phone' => '+989145509000',
            'code' => '12ab56',
        ]);
        $responseAlpha->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_verify_wrong_code_increments_attempts_and_returns_code_not_exists(): void
    {
        $phone = '+989145509000';
        $correctCode = '112233';
        $wrongCode = '000000';
        $ip = '198.51.100.120';
        $deviceId = 'dev-wrong-1';

        $otp = OtpToken::create([
            'phone' => $phone,
            'code_hash' => $correctCode,
            'purpose' => OtpPurpose::LOGIN,
            'expires_at' => now()->addMinutes(5),
            'request_ip' => $ip,
            'device_id' => $deviceId,
        ]);
        OtpCache::set($otp);

        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/v1/otp/verify', [
                'phone' => $phone,
                'code' => $wrongCode,
            ], [
                'X-Device-Id' => $deviceId,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $errors = $response->json('errors.code');
        $this->assertIsArray($errors);
        $this->assertSame(trans('otp.code_not_exists'), $errors[0]);

        $otp->refresh();
        $this->assertSame(1, $otp->attempts_count);
    }

    public function test_verify_max_attempts_returns_code_max_attempts(): void
    {
        $phone = '+989145509000';
        $code = '333444';
        $ip = '192.0.2.200';
        $deviceId = 'dev-max-1';
        $max = 3;

        Config::set('otp.length', 6);
        Config::set('otp.max_attempts', $max);

        $otp = OtpToken::create([
            'phone' => $phone,
            'code_hash' => $code,
            'purpose' => OtpPurpose::LOGIN,
            'expires_at' => now()->addMinutes(5),
            'request_ip' => $ip,
            'device_id' => $deviceId,
            'attempts_count' => $max,
            'max_attempts' => $max,
        ]);
        OtpCache::set($otp);

        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/v1/otp/verify', [
                'phone' => $phone,
                'code' => $code,
            ], [
                'X-Device-Id' => $deviceId,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $errors = $response->json('errors.code');
        $this->assertIsArray($errors);
        $this->assertSame(trans('otp.code_max_attempts', ['count' => $max]), $errors[0]);

        $otp->refresh();
        $this->assertSame($max + 1, $otp->attempts_count);
    }

    public function test_verify_fails_with_ip_mismatch_when_ip_differs(): void
    {
        $phone = '+989145509000';
        $code = '121212';
        $correctIp = '203.0.113.10';
        $wrongIp = '203.0.113.11';
        $deviceId = 'dev-bind-1';

        $otp = OtpToken::create([
            'phone' => $phone,
            'code_hash' => $code,
            'purpose' => OtpPurpose::REGISTER,
            'expires_at' => now()->addMinutes(5),
            'request_ip' => $correctIp,
            'device_id' => $deviceId,
        ]);
        OtpCache::set($otp);

        $response = $this->withServerVariables(['REMOTE_ADDR' => $wrongIp])
            ->postJson('/api/v1/otp/verify', [
                'phone' => $phone,
                'code' => $code,
            ], [
                'X-Device-Id' => $deviceId,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $errors = $response->json('errors.code');
        $this->assertIsArray($errors);
        $this->assertSame(trans('otp.ip_mismatch'), $errors[0]);

        $otp->refresh();
        $this->assertSame(0, $otp->attempts_count);
    }

    public function test_verify_fails_with_ip_mismatch_when_device_id_differs(): void
    {
        $phone = '+989145509000';
        $code = '343434';
        $ip = '198.51.100.33';
        $correctDevice = 'dev-bind-2';
        $wrongDevice = 'dev-bind-3';

        $otp = OtpToken::create([
            'phone' => $phone,
            'code_hash' => $code,
            'purpose' => OtpPurpose::LOGIN,
            'expires_at' => now()->addMinutes(5),
            'request_ip' => $ip,
            'device_id' => $correctDevice,
        ]);
        OtpCache::set($otp);

        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/v1/otp/verify', [
                'phone' => $phone,
                'code' => $code,
            ], [
                'X-Device-Id' => $wrongDevice,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $errors = $response->json('errors.code');
        $this->assertIsArray($errors);
        $this->assertSame(trans('otp.ip_mismatch'), $errors[0]);

        $otp->refresh();
        $this->assertSame(0, $otp->attempts_count);
    }

    public function test_verify_after_resend_only_latest_code_is_accepted(): void
    {
        $phone = '+989145509000';
        $ip = '203.0.113.44';
        $deviceId = 'dev-resend-1';

        $oldCode = '111222';
        $newCode = '333444';

        // First issued OTP (older)
        $old = OtpToken::create([
            'phone' => $phone,
            'code_hash' => $oldCode,
            'purpose' => OtpPurpose::REGISTER,
            'expires_at' => now()->addMinutes(5),
            'request_ip' => $ip,
            'device_id' => $deviceId,
        ]);

        // Second issued OTP (latest active)
        $latest = OtpToken::create([
            'phone' => $phone,
            'code_hash' => $newCode,
            'purpose' => OtpPurpose::LOGIN,
            'expires_at' => now()->addMinutes(5),
            'request_ip' => $ip,
            'device_id' => $deviceId,
        ]);

        // Cache should point to the latest token as the send endpoint would do
        OtpCache::set($latest);

        // Try verifying with OLD code -> should fail and increment attempts on the LATEST token
        $respOld = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/v1/otp/verify', [
                'phone' => $phone,
                'code' => $oldCode,
            ], [
                'X-Device-Id' => $deviceId,
            ]);

        $respOld->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
        $this->assertSame(trans('otp.code_not_exists'), $respOld->json('errors.code.0'));

        $latest->refresh();
        $old->refresh();
        $this->assertSame(1, $latest->attempts_count, 'Latest token attempts should increment');
        $this->assertSame(0, $old->attempts_count, 'Old token attempts should remain unchanged');

        // Now verify with NEW code -> should succeed and consume the latest token
        $respNew = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/v1/otp/verify', [
                'phone' => $phone,
                'code' => $newCode,
            ], [
                'X-Device-Id' => $deviceId,
            ]);

        $respNew->assertOk()
            ->assertJsonStructure([
                'next_step',
                'token' => ['access_token', 'token_type', 'expires_in'],
                'message',
            ]);
        $this->assertSame('panel', $respNew->json('next_step'));

        $latest->refresh();
        $this->assertNotNull($latest->consumed_at);
        $this->assertNull(OtpCache::get($phone));
    }

    public function test_verify_expired_code_fails_with_code_not_exists(): void
    {
        $phone = '+989145509000';
        $code = '555666';
        $ip = '198.51.100.55';
        $deviceId = 'dev-exp-1';

        // Create an already-expired OTP token
        $otp = OtpToken::create([
            'phone' => $phone,
            'code_hash' => $code,
            'purpose' => OtpPurpose::REGISTER,
            'expires_at' => now()->subMinute(),
            'request_ip' => $ip,
            'device_id' => $deviceId,
        ]);

        // Cache would be missing for expired tokens; do not set cache

        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/v1/otp/verify', [
                'phone' => $phone,
                'code' => $code,
            ], [
                'X-Device-Id' => $deviceId,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $this->assertSame(trans('otp.code_not_exists'), $response->json('errors.code.0'));

        $otp->refresh();
        $this->assertSame(0, $otp->attempts_count, 'Attempts should not increment when cache is missing');
    }

    public function test_verify_consumption_prevents_reuse_same_code(): void
    {
        $phone = '+989145509000';
        $code = '777888';
        $ip = '203.0.113.66';
        $deviceId = 'dev-consume-1';

        $otp = OtpToken::create([
            'phone' => $phone,
            'code_hash' => $code,
            'purpose' => OtpPurpose::LOGIN,
            'expires_at' => now()->addMinutes(5),
            'request_ip' => $ip,
            'device_id' => $deviceId,
        ]);
        OtpCache::set($otp);

        // First verify succeeds and consumes token
        $first = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/v1/otp/verify', [
                'phone' => $phone,
                'code' => $code,
            ], [
                'X-Device-Id' => $deviceId,
            ]);
        $first->assertOk();

        $otp->refresh();
        $this->assertNotNull($otp->consumed_at);
        $this->assertSame(1, $otp->attempts_count, 'attempts should increment on successful validation');
        $this->assertNull(OtpCache::get($phone));

        // Second verify with same code should fail because token is consumed and cache removed
        $second = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/v1/otp/verify', [
                'phone' => $phone,
                'code' => $code,
            ], [
                'X-Device-Id' => $deviceId,
            ]);

        $second->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
        $this->assertSame(trans('otp.code_not_exists'), $second->json('errors.code.0'));

        $otp->refresh();
        $this->assertSame(1, $otp->attempts_count, 'attempts should not increment after consumption/cache-miss');
    }
}


