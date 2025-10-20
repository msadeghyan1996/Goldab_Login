<?php

namespace Tests\Unit\Services;

use App\Models\Enum\OtpPurpose;
use App\Models\OtpToken;
use App\Services\Otp\OtpCache;
use App\Services\Otp\SendOtpService;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Mockery;
use Tests\TestCase;

class SendOtpServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_persists_token_caches_and_calls_sms_once_with_numeric_code_of_configured_length(): void
    {
        Date::setTestNow(now());
        Config::set('otp.length', 6);
        Config::set('otp.ttl_minutes', 2);

        $phone = '+989145509000';
        $purpose = OtpPurpose::REGISTER;
        $deviceId = 'device-123';
        $ip = '203.0.113.10';

        $capturedCode = null;

        $sms = Mockery::mock(SmsService::class);
        $sms->shouldReceive('send')
            ->once()
            ->withArgs(function (string $p, OtpPurpose $purp, string $code) use (&$capturedCode, $phone, $purpose) {
                $this->assertSame($phone, $p);
                $this->assertSame($purpose, $purp);
                $this->assertTrue(ctype_digit($code));
                $this->assertSame(config('otp.length', 6), strlen($code));
                $capturedCode = $code;
                return true;
            });

        $service = new SendOtpService($sms);

        $request = Request::create('/api/v1/otp/send', 'POST', ['phone' => $phone], [], [], ['REMOTE_ADDR' => $ip]);
        $request->headers->set('X-Device-Id', $deviceId);

        $otp = $service->issue($phone, $purpose, $request);

        $this->assertInstanceOf(OtpToken::class, $otp);
        $this->assertNotNull($capturedCode);

        $this->assertDatabaseHas('otp_tokens', [
            'id' => $otp->id,
            'phone' => $phone,
            'purpose' => $purpose->value,
            'attempts_count' => 0,
            'max_attempts' => config('otp.max_attempts', 5),
            'request_ip' => $ip,
            'device_id' => $deviceId,
        ]);

        $cached = OtpCache::get($phone);
        $this->assertNotNull($cached);
        $this->assertSame($otp->id, $cached->id);
    }

    public function test_issue_sets_expiration_in_future_and_metadata(): void
    {
        Date::setTestNow(now());
        Config::set('otp.length', 6);
        Config::set('otp.ttl_minutes', 3);

        $phone = '+989145509000';
        $purpose = OtpPurpose::LOGIN;
        $deviceId = 'dev-999';
        $ip = '198.51.100.5';

        $sms = Mockery::mock(SmsService::class);
        $sms->shouldReceive('send')->once()->andReturnNull();

        $service = new SendOtpService($sms);

        $request = Request::create('/api/v1/otp/send', 'POST', ['phone' => $phone], [], [], ['REMOTE_ADDR' => $ip]);
        $request->headers->set('X-Device-Id', $deviceId);

        $otp = $service->issue($phone, $purpose, $request);

        $this->assertTrue($otp->expires_at->greaterThan(now()));
        $this->assertTrue($otp->expires_at->lessThanOrEqualTo(now()->addMinutes(3)));

        $this->assertSame($ip, $otp->request_ip);
        $this->assertSame($deviceId, $otp->device_id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}


