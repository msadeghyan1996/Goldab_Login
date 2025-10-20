<?php

namespace Tests\Unit\Rules;

use App\Models\Enum\OtpPurpose;
use App\Models\OtpToken;
use App\Rules\Otp\ValidateOtpCode;
use App\Services\Otp\OtpCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class ValidateOtpCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Date::setTestNow(now());
        Config::set('otp.pepper', 'test-pepper');
    }

    public function test_fails_when_cache_missing(): void
    {
        $phone = '+989145509000';

        $request = Request::create('/api/v1/otp/verify', 'POST');
        $request->server->set('REMOTE_ADDR', '203.0.113.10');
        $request->headers->set('X-Device-Id', 'dev-1');
        $this->app->instance('request', $request);

        $rule = new ValidateOtpCode($phone);
        $errors = [];

        $rule->validate('code', '000000', function (string $message) use (&$errors) {
            $errors[] = $message;
        });

        $this->assertNotEmpty($errors);
        $this->assertSame(trans('otp.code_not_exists'), $errors[0]);
    }

    public function test_enforces_ip_and_device_and_does_not_increment_attempts_on_mismatch(): void
    {
        $phone = '+989145509000';
        $correctIp = '203.0.113.10';
        $correctDevice = 'device-abc';

        $otp = OtpToken::create([
            'phone' => $phone,
            'code_hash' => '123456',
            'purpose' => OtpPurpose::REGISTER,
            'expires_at' => now()->addMinutes(5),
            'request_ip' => $correctIp,
            'device_id' => $correctDevice,
        ]);
        OtpCache::set($otp);

        $request = Request::create('/api/v1/otp/verify', 'POST');
        $request->server->set('REMOTE_ADDR', '203.0.113.99'); // mismatched IP
        $request->headers->set('X-Device-Id', 'device-xyz'); // mismatched device
        $this->app->instance('request', $request);

        $rule = new ValidateOtpCode($phone);
        $errors = [];
        $rule->validate('code', '123456', function (string $message) use (&$errors) {
            $errors[] = $message;
        });

        $this->assertNotEmpty($errors);
        $this->assertSame(trans('otp.ip_mismatch'), $errors[0]);

        $otp->refresh();
        $this->assertSame(0, $otp->attempts_count, 'Attempts should not increment on IP/Device mismatch');
    }

    public function test_increments_attempts_on_wrong_code_and_returns_error(): void
    {
        $phone = '+989145509000';
        $ip = '198.51.100.10';
        $device = 'dev-2';

        $otp = OtpToken::create([
            'phone' => $phone,
            'code_hash' => '654321', // mutator hashes actual code
            'purpose' => OtpPurpose::LOGIN,
            'expires_at' => now()->addMinutes(5),
            'request_ip' => $ip,
            'device_id' => $device,
        ]);
        OtpCache::set($otp);

        $request = Request::create('/api/v1/otp/verify', 'POST');
        $request->server->set('REMOTE_ADDR', $ip);
        $request->headers->set('X-Device-Id', $device);
        $this->app->instance('request', $request);

        $rule = new ValidateOtpCode($phone);
        $errors = [];
        $rule->validate('code', '000000', function (string $message) use (&$errors) {
            $errors[] = $message;
        });

        $this->assertNotEmpty($errors);
        $this->assertSame(trans('otp.code_not_exists'), $errors[0]);

        $otp->refresh();
        $this->assertSame(1, $otp->attempts_count);
    }

    public function test_blocks_after_max_attempts(): void
    {
        $phone = '+989145509000';
        $ip = '203.0.113.50';
        $device = 'dev-3';
        $max = (int) config('otp.max_attempts', 5);

        $otp = OtpToken::create([
            'phone' => $phone,
            'code_hash' => '111222',
            'purpose' => OtpPurpose::REGISTER,
            'expires_at' => now()->addMinutes(5),
            'request_ip' => $ip,
            'device_id' => $device,
            'attempts_count' => $max,
            'max_attempts' => $max,
        ]);
        OtpCache::set($otp);

        $request = Request::create('/api/v1/otp/verify', 'POST');
        $request->server->set('REMOTE_ADDR', $ip);
        $request->headers->set('X-Device-Id', $device);
        $this->app->instance('request', $request);

        $rule = new ValidateOtpCode($phone);
        $errors = [];
        $rule->validate('code', '111222', function (string $message) use (&$errors) {
            $errors[] = $message;
        });

        $this->assertNotEmpty($errors);
        $this->assertSame(trans('otp.code_max_attempts', ['count' => $max]), $errors[0]);

        $otp->refresh();
        $this->assertSame($max + 1, $otp->attempts_count, 'attempt() is called before the max check');
    }

    public function test_passes_on_correct_code(): void
    {
        $phone = '+989145509000';
        $ip = '192.0.2.10';
        $device = 'dev-4';
        $correct = '999000';

        $otp = OtpToken::create([
            'phone' => $phone,
            'code_hash' => $correct, // mutator stores salted+peppered hash
            'purpose' => OtpPurpose::LOGIN,
            'expires_at' => now()->addMinutes(5),
            'request_ip' => $ip,
            'device_id' => $device,
        ]);
        OtpCache::set($otp);

        $request = Request::create('/api/v1/otp/verify', 'POST');
        $request->server->set('REMOTE_ADDR', $ip);
        $request->headers->set('X-Device-Id', $device);
        $this->app->instance('request', $request);

        $rule = new ValidateOtpCode($phone);
        $errors = [];
        $rule->validate('code', $correct, function (string $message) use (&$errors) {
            $errors[] = $message;
        });

        $this->assertEmpty($errors, 'Validation should pass on correct code');

        $otp->refresh();
        $this->assertSame(1, $otp->attempts_count, 'attempts should increment even on success');
    }
}


