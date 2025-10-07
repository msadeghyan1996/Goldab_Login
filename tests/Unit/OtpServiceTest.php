<?php

namespace Tests\Unit;

use App\Models\OtpCode;
use App\Repositories\OtpRepositoryInterface;
use App\Services\impl\OtpService;
use Tests\TestCase;
use Mockery;

class OtpServiceTest extends TestCase
{
    protected $otpRepository;
    protected $otpService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->otpRepository = Mockery::mock(OtpRepositoryInterface::class);
        $this->otpService = new OtpService($this->otpRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_generate_otp_creates_6_digit_code()
    {
        $mobileNumber = '9145813194';
        $purpose = OtpCode::PURPOSE_REGISTRATION;

        $this->otpRepository->shouldReceive('countRecentOtps')
            ->once()
            ->with($mobileNumber, 15)
            ->andReturn(0);

        $this->otpRepository->shouldReceive('invalidatePreviousOtps')
            ->once()
            ->with($mobileNumber, $purpose);

        $mockOtp = Mockery::mock(OtpCode::class);
        $mockOtp->expires_at = now()->addMinutes(5);

        $this->otpRepository->shouldReceive('createOtp')
            ->once()
            ->andReturn($mockOtp);

        $result = $this->otpService->generateOtp($mobileNumber, $purpose);

        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('otp', $result);
        $this->assertEquals(6, strlen($result['code']));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $result['code']);
    }

    public function test_generate_otp_throws_exception_on_rate_limit()
    {
        $mobileNumber = '9145813194';
        $purpose = OtpCode::PURPOSE_REGISTRATION;

        $this->otpRepository->shouldReceive('countRecentOtps')
            ->once()
            ->with($mobileNumber, 15)
            ->andReturn(5); // Exceeds rate limit

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Too many OTP requests');

        $this->otpService->generateOtp($mobileNumber, $purpose);
    }

    public function test_verify_otp_succeeds_with_valid_code()
    {
        $mobileNumber = '9145813194';
        $code = '123456';
        $purpose = OtpCode::PURPOSE_REGISTRATION;

        $mockOtp = Mockery::mock(OtpCode::class);
        $mockOtp->shouldReceive('hasExceededAttempts')->andReturn(false);
        $mockOtp->shouldReceive('incrementAttempts')->never();
        $mockOtp->shouldReceive('markAsUsed')->once();
        $mockOtp->code_hash = hash('sha256', $code . env('APP_KEY'));

        $this->otpRepository->shouldReceive('findValidOtp')
            ->once()
            ->with($mobileNumber, $purpose)
            ->andReturn($mockOtp);

        $result = $this->otpService->verifyOtp($mobileNumber, $code, $purpose);

        $this->assertTrue($result);
    }

    public function test_verify_otp_fails_with_invalid_code()
    {
        $mobileNumber = '9145813194';
        $code = '123456';
        $wrongCode = '654321';
        $purpose = OtpCode::PURPOSE_REGISTRATION;

        $mockOtp = Mockery::mock(OtpCode::class);
        $mockOtp->shouldReceive('hasExceededAttempts')->andReturn(false);
        $mockOtp->shouldReceive('incrementAttempts')->once();
        $mockOtp->shouldReceive('markAsUsed')->never();
        $mockOtp->code_hash = hash('sha256', $code . env('APP_KEY'));
        $mockOtp->attempts = 1;

        $this->otpRepository->shouldReceive('findValidOtp')
            ->once()
            ->with($mobileNumber, $purpose)
            ->andReturn($mockOtp);

        $result = $this->otpService->verifyOtp($mobileNumber, $wrongCode, $purpose);

        $this->assertFalse($result);
    }

    public function test_verify_otp_fails_when_attempts_exceeded()
    {
        $mobileNumber = '9145813194';
        $code = '123456';
        $purpose = OtpCode::PURPOSE_REGISTRATION;

        $mockOtp = Mockery::mock(OtpCode::class);
        $mockOtp->shouldReceive('hasExceededAttempts')->andReturn(true);
        $mockOtp->attempts = 3;

        $this->otpRepository->shouldReceive('findValidOtp')
            ->once()
            ->with($mobileNumber, $purpose)
            ->andReturn($mockOtp);

        $result = $this->otpService->verifyOtp($mobileNumber, $code, $purpose);

        $this->assertFalse($result);
    }

    public function test_verify_otp_returns_false_when_otp_not_found()
    {
        $mobileNumber = '9145813194';
        $code = '123456';
        $purpose = OtpCode::PURPOSE_REGISTRATION;

        $this->otpRepository->shouldReceive('findValidOtp')
            ->once()
            ->with($mobileNumber, $purpose)
            ->andReturn(null);

        $result = $this->otpService->verifyOtp($mobileNumber, $code, $purpose);

        $this->assertFalse($result);
    }
}

