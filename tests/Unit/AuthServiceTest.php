<?php

namespace Tests\Unit;

use App\Models\OtpCode;
use App\Models\User;
use App\Repositories\LoginAttemptRepositoryInterface;
use App\Repositories\UserRepositoryInterface;
use App\Services\impl\AuthService;
use App\Services\OtpServiceInterface;
use Tests\TestCase;
use Mockery;

class AuthServiceTest extends TestCase
{
    protected $userRepository;
    protected $otpService;
    protected $loginAttemptRepository;
    protected $authService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->otpService = Mockery::mock(OtpServiceInterface::class);
        $this->loginAttemptRepository = Mockery::mock(LoginAttemptRepositoryInterface::class);
        
        $this->authService = new AuthService(
            $this->userRepository,
            $this->otpService,
            $this->loginAttemptRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_check_mobile_returns_exists_true_for_registered_user()
    {
        $mobileNumber = '9145813194';
        
        $mockUser = Mockery::mock(User::class);
        $mockUser->shouldReceive('hasCompletedRegistration')->andReturn(true);

        $this->userRepository->shouldReceive('findByMobileNumber')
            ->once()
            ->with($mobileNumber)
            ->andReturn($mockUser);

        $result = $this->authService->checkMobile($mobileNumber);

        $this->assertTrue($result['exists']);
        $this->assertTrue($result['requires_password']);
        $this->assertFalse($result['requires_registration']);
    }

    public function test_check_mobile_returns_exists_false_for_new_user()
    {
        $mobileNumber = '9145813194';

        $this->userRepository->shouldReceive('findByMobileNumber')
            ->once()
            ->with($mobileNumber)
            ->andReturn(null);

        $result = $this->authService->checkMobile($mobileNumber);

        $this->assertFalse($result['exists']);
        $this->assertFalse($result['requires_password']);
        $this->assertTrue($result['requires_registration']);
    }

    public function test_login_with_password_succeeds_with_valid_credentials()
    {
        $mobileNumber = '9145813194';
        $password = 'TestPassword123';
        $ipAddress = '127.0.0.1';
        $userAgent = 'Test Browser';

        $mockUser = Mockery::mock(User::class);
        $mockUser->id = 1;
        $mockUser->mobile_number = $mobileNumber;
        $mockUser->password = password_hash($password, PASSWORD_BCRYPT);

        $this->loginAttemptRepository->shouldReceive('isAccountLocked')
            ->once()
            ->with($mobileNumber)
            ->andReturn(false);

        $this->userRepository->shouldReceive('findByMobileNumber')
            ->once()
            ->with($mobileNumber)
            ->andReturn($mockUser);

        $this->loginAttemptRepository->shouldReceive('logAttempt')
            ->once()
            ->with($mobileNumber, $ipAddress, true, $userAgent);

        $result = $this->authService->loginWithPassword($mobileNumber, $password, $ipAddress, $userAgent);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
    }

    public function test_login_with_password_fails_with_invalid_credentials()
    {
        $mobileNumber = '9145813194';
        $password = 'TestPassword123';
        $wrongPassword = 'WrongPassword';
        $ipAddress = '127.0.0.1';
        $userAgent = 'Test Browser';

        $mockUser = Mockery::mock(User::class);
        $mockUser->password = password_hash($password, PASSWORD_BCRYPT);

        $this->loginAttemptRepository->shouldReceive('isAccountLocked')
            ->once()
            ->with($mobileNumber)
            ->andReturn(false);

        $this->userRepository->shouldReceive('findByMobileNumber')
            ->once()
            ->with($mobileNumber)
            ->andReturn($mockUser);

        $this->loginAttemptRepository->shouldReceive('logAttempt')
            ->once()
            ->with($mobileNumber, $ipAddress, false, $userAgent);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->authService->loginWithPassword($mobileNumber, $wrongPassword, $ipAddress, $userAgent);
    }

    public function test_login_fails_when_account_is_locked()
    {
        $mobileNumber = '9145813194';
        $password = 'TestPassword123';
        $ipAddress = '127.0.0.1';

        $this->loginAttemptRepository->shouldReceive('isAccountLocked')
            ->once()
            ->with($mobileNumber)
            ->andReturn(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Account is temporarily locked');

        $this->authService->loginWithPassword($mobileNumber, $password, $ipAddress);
    }

    public function test_initiate_registration_generates_otp()
    {
        $mobileNumber = '9145813194';
        
        $mockOtp = Mockery::mock(OtpCode::class);
        $mockOtp->expires_at = now()->addMinutes(5);

        $this->otpService->shouldReceive('generateOtp')
            ->once()
            ->with($mobileNumber, OtpCode::PURPOSE_REGISTRATION)
            ->andReturn([
                'code' => '123456',
                'otp' => $mockOtp,
            ]);

        $result = $this->authService->initiateRegistration($mobileNumber);

        $this->assertTrue($result['success']);
        $this->assertEquals('OTP sent successfully', $result['message']);
    }

    public function test_complete_registration_creates_user_with_valid_otp()
    {
        $mobileNumber = '9145813194';
        $otpCode = '123456';
        $profileData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'national_id' => '1234567890',
            'password' => 'TestPassword123',
        ];

        $mockUser = Mockery::mock(User::class);
        $mockUser->id = 1;
        $mockUser->mobile_number = $mobileNumber;
        $mockUser->shouldReceive('verifyMobile')->once();
        $mockUser->shouldReceive('refresh')->once();

        $this->otpService->shouldReceive('verifyOtp')
            ->once()
            ->with($mobileNumber, $otpCode, OtpCode::PURPOSE_REGISTRATION)
            ->andReturn(true);

        $this->userRepository->shouldReceive('nationalIdExists')
            ->once()
            ->with($profileData['national_id'])
            ->andReturn(false);

        $this->userRepository->shouldReceive('findByMobileNumber')
            ->once()
            ->with($mobileNumber)
            ->andReturn($mockUser);

        $this->userRepository->shouldReceive('updateProfile')
            ->once();

        $result = $this->authService->completeRegistration($mobileNumber, $otpCode, $profileData);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
    }

    public function test_complete_registration_fails_with_invalid_otp()
    {
        $mobileNumber = '9145813194';
        $otpCode = '123456';
        $profileData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'national_id' => '1234567890',
            'password' => 'TestPassword123',
        ];

        $this->otpService->shouldReceive('verifyOtp')
            ->once()
            ->with($mobileNumber, $otpCode, OtpCode::PURPOSE_REGISTRATION)
            ->andReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid or expired OTP code');

        $this->authService->completeRegistration($mobileNumber, $otpCode, $profileData);
    }

    public function test_complete_registration_fails_with_duplicate_national_id()
    {
        $mobileNumber = '9145813194';
        $otpCode = '123456';
        $profileData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'national_id' => '1234567890',
            'password' => 'TestPassword123',
        ];

        $this->otpService->shouldReceive('verifyOtp')
            ->once()
            ->andReturn(true);

        $this->userRepository->shouldReceive('nationalIdExists')
            ->once()
            ->with($profileData['national_id'])
            ->andReturn(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('National ID already registered');

        $this->authService->completeRegistration($mobileNumber, $otpCode, $profileData);
    }
}

