<?php

namespace Tests\Feature\Otp;

use App\Models\Enum\OtpPurpose;
use App\Models\User;
use App\Services\Otp\OtpCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class SendOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_returns_201_with_message_expires_in_and_expires_at(): void
    {
        Date::setTestNow(now());
        Config::set('otp.ttl_minutes', 2);

        $phone = '+989145509000';
        $deviceId = 'device-101';

        $response = $this->postJson('/api/v1/otp/send', [
            'phone' => $phone,
        ], [
            'X-Device-Id' => $deviceId,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'expires_in',
                'expires_at',
            ]);

        $data = $response->json();

        $this->assertIsInt($data['expires_at']);
        $this->assertSame(now()->addMinutes(2)->timestamp, $data['expires_at']);

        $this->assertDatabaseHas('otp_tokens', [
            'phone' => $phone,
            'device_id' => $deviceId,
        ]);

        $this->assertNotNull(OtpCache::get($phone));
    }

    public function test_send_validation_rejects_missing_phone(): void
    {
        $response = $this->postJson('/api/v1/otp/send', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_send_validation_rejects_invalid_phone(): void
    {
        $response = $this->postJson('/api/v1/otp/send', [
            'phone' => 'not-a-valid-phone',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_send_sets_purpose_register_when_user_not_verified(): void
    {
        $phone = '+989145509000';
        $deviceId = 'dev-reg-1';

        // No user exists (or could create one without national_id) -> REGISTER
        $response = $this->postJson('/api/v1/otp/send', [
            'phone' => $phone,
        ], [
            'X-Device-Id' => $deviceId,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('otp_tokens', [
            'phone' => $phone,
            'purpose' => OtpPurpose::REGISTER->value,
        ]);
    }

    public function test_send_sets_purpose_login_when_user_verified(): void
    {
        $phone = '+989145509000';
        $deviceId = 'dev-log-1';

        User::create([
            'phone' => $phone,
            'national_id' => 'NID-123456',
        ]);

        $response = $this->postJson('/api/v1/otp/send', [
            'phone' => $phone,
        ], [
            'X-Device-Id' => $deviceId,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('otp_tokens', [
            'phone' => $phone,
            'purpose' => OtpPurpose::LOGIN->value,
        ]);
    }

    public function test_send_rate_limit_cooldown_per_ip_returns_429_with_retry_after(): void
    {
        Date::setTestNow(now());
        Config::set('otp.resend_cooldown_seconds', 120);

        $ip = '203.0.113.200';
        $phone1 = '+989145509000';
        $phone2 = '+989145509000';

        // First successful send from IP should be allowed
        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/v1/otp/send', ['phone' => $phone1])
            ->assertStatus(201);

        // Immediate second send from same IP (different phone) should hit IP cooldown
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/v1/otp/send', ['phone' => $phone2]);

        $response->assertStatus(429)
            ->assertJsonStructure(['message', 'retry_after']);

        $this->assertSame(trans('otp.limit.send'), $response->json('message'));
        $this->assertIsInt($response->json('retry_after'));
        $this->assertGreaterThanOrEqual(1, $response->json('retry_after'));
    }

    public function test_send_rate_limit_cooldown_per_phone_returns_429_with_retry_after(): void
    {
        Date::setTestNow(now());
        Config::set('otp.resend_cooldown_seconds', 120);

        $phone = '+989145509000';
        $ipA = '198.51.100.10';
        $ipB = '198.51.100.11';

        // First successful send for this phone from IP A
        $this->withServerVariables(['REMOTE_ADDR' => $ipA])
            ->postJson('/api/v1/otp/send', ['phone' => $phone])
            ->assertStatus(201);

        // Immediate second send for SAME phone from different IP should hit phone-based cooldown
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ipB])
            ->postJson('/api/v1/otp/send', ['phone' => $phone]);

        $response->assertStatus(429)
            ->assertJsonStructure(['message', 'retry_after']);

        $this->assertSame(trans('otp.limit.send'), $response->json('message'));
        $this->assertIsInt($response->json('retry_after'));
        $this->assertGreaterThanOrEqual(1, $response->json('retry_after'));
    }

    public function test_send_hourly_limit_per_ip_returns_429_with_retry_after(): void
    {
        Date::setTestNow(now());
        Config::set('otp.max_sends_per_hour', 2);
        Config::set('otp.resend_cooldown_seconds', 1); // keep low to avoid long waits

        $ip = '203.0.113.250';

        // 1st send: allowed
        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/v1/otp/send', ['phone' => '+989145509000'])
            ->assertStatus(201);

        // Wait beyond cooldown
        Date::setTestNow(now()->addSeconds(2));

        // 2nd send: allowed (still within the same hour window)
        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/v1/otp/send', ['phone' => '+989145509001'])
            ->assertStatus(201);

        // Wait beyond cooldown
        Date::setTestNow(now()->addSeconds(2));

        // 3rd send: should hit hourly per-IP limit -> 429 with retry_after
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/v1/otp/send', ['phone' => '+989145509000']);

        $response->assertStatus(429)
            ->assertJsonStructure(['message', 'retry_after']);

        $this->assertSame(trans('otp.limit.send'), $response->json('message'));
        $this->assertIsInt($response->json('retry_after'));
        $this->assertGreaterThanOrEqual(1, $response->json('retry_after'));
    }
}


