<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class RegistrationTest extends TestCase
{
    public function test_request_otp_sends_otp_for_new_mobile_number()
    {
        $response = $this->json('POST', '/api/v1/auth/request-otp', [
            'mobile_number' => '9145813194',
        ]);

        $response->assertResponseStatus(200);
        $response->seeJsonStructure([
            'success',
            'message',
            'data' => [
                'success',
                'message',
            ],
        ]);
        
        $data = json_decode($response->response->getContent(), true);
        $this->assertTrue($data['success']);

        // Verify OTP was created in database
        $this->seeInDatabase('otp_codes', [
            'mobile_number' => '9145813194',
            'purpose' => OtpCode::PURPOSE_REGISTRATION,
            'is_used' => false,
        ]);
    }

    public function test_request_otp_validates_mobile_number_format()
    {
        $response = $this->json('POST', '/api/v1/auth/request-otp', [
            'mobile_number' => '123',
        ]);

        $response->assertResponseStatus(422);
    }

    public function test_request_otp_enforces_rate_limiting()
    {
        $mobileNumber = '9145813194';

        // Make multiple requests
        for ($i = 0; $i < 3; $i++) {
            $this->json('POST', '/api/v1/auth/request-otp', [
                'mobile_number' => $mobileNumber,
            ]);
        }

        // The 4th request should fail due to rate limiting
        $response = $this->json('POST', '/api/v1/auth/request-otp', [
            'mobile_number' => $mobileNumber,
        ]);

        $response->assertResponseStatus(400);
        $data = json_decode($response->response->getContent(), true);
        $this->assertStringContainsString('Too many OTP requests', $data['message']);
    }

    public function test_register_completes_registration_with_valid_otp()
    {
        $mobileNumber = '9145813194';
        $otpCode = '123456';

        // Create OTP
        $codeHash = hash('sha256', $otpCode . env('APP_KEY'));
        OtpCode::create([
            'mobile_number' => $mobileNumber,
            'code_hash' => $codeHash,
            'purpose' => OtpCode::PURPOSE_REGISTRATION,
            'expires_at' => now()->addMinutes(5),
            'is_used' => false,
            'attempts' => 0,
        ]);

        $response = $this->json('POST', '/api/v1/auth/register', [
            'mobile_number' => $mobileNumber,
            'otp_code' => $otpCode,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'national_id' => '1234567890',
            'password' => 'TestPassword123',
            'password_confirmation' => 'TestPassword123',
        ]);

        $response->assertResponseStatus(201);
        $response->seeJsonStructure([
            'success',
            'message',
            'data' => [
                'user',
                'token',
            ],
        ]);

        // Verify user was created
        $this->seeInDatabase('users', [
            'mobile_number' => $mobileNumber,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'national_id' => '1234567890',
            'is_verified' => true,
        ]);

        // Verify OTP was marked as used
        $this->seeInDatabase('otp_codes', [
            'mobile_number' => $mobileNumber,
            'is_used' => true,
        ]);
    }

    public function test_register_fails_with_invalid_otp()
    {
        $mobileNumber = '9145813194';
        $otpCode = '123456';
        $wrongOtp = '654321';

        // Create OTP
        $codeHash = hash('sha256', $otpCode . env('APP_KEY'));
        OtpCode::create([
            'mobile_number' => $mobileNumber,
            'code_hash' => $codeHash,
            'purpose' => OtpCode::PURPOSE_REGISTRATION,
            'expires_at' => now()->addMinutes(5),
            'is_used' => false,
            'attempts' => 0,
        ]);

        $response = $this->json('POST', '/api/v1/auth/register', [
            'mobile_number' => $mobileNumber,
            'otp_code' => $wrongOtp,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'national_id' => '1234567890',
            'password' => 'TestPassword123',
            'password_confirmation' => 'TestPassword123',
        ]);

        $response->assertResponseStatus(400);
        
        // Verify user was not created
        $this->notSeeInDatabase('users', [
            'mobile_number' => $mobileNumber,
        ]);
    }

    public function test_register_fails_with_expired_otp()
    {
        $mobileNumber = '9145813194';
        $otpCode = '123456';

        // Create expired OTP
        $codeHash = hash('sha256', $otpCode . env('APP_KEY'));
        OtpCode::create([
            'mobile_number' => $mobileNumber,
            'code_hash' => $codeHash,
            'purpose' => OtpCode::PURPOSE_REGISTRATION,
            'expires_at' => now()->subMinutes(5), // Expired
            'is_used' => false,
            'attempts' => 0,
        ]);

        $response = $this->json('POST', '/api/v1/auth/register', [
            'mobile_number' => $mobileNumber,
            'otp_code' => $otpCode,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'national_id' => '1234567890',
            'password' => 'TestPassword123',
            'password_confirmation' => 'TestPassword123',
        ]);

        $response->assertResponseStatus(400);
    }

    public function test_register_validates_required_fields()
    {
        $response = $this->json('POST', '/api/v1/auth/register', [
            'mobile_number' => '9145813194',
            'otp_code' => '123456',
        ]);

        $response->assertResponseStatus(422);
    }

    public function test_register_validates_national_id_format()
    {
        $mobileNumber = '9145813194';
        $otpCode = '123456';

        // Create OTP
        $codeHash = hash('sha256', $otpCode . env('APP_KEY'));
        OtpCode::create([
            'mobile_number' => $mobileNumber,
            'code_hash' => $codeHash,
            'purpose' => OtpCode::PURPOSE_REGISTRATION,
            'expires_at' => now()->addMinutes(5),
            'is_used' => false,
            'attempts' => 0,
        ]);

        $response = $this->json('POST', '/api/v1/auth/register', [
            'mobile_number' => $mobileNumber,
            'otp_code' => $otpCode,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'national_id' => '123', // Invalid format
            'password' => 'TestPassword123',
            'password_confirmation' => 'TestPassword123',
        ]);

        $response->assertResponseStatus(422);
    }

    public function test_register_validates_password_confirmation()
    {
        $mobileNumber = '9145813194';
        $otpCode = '123456';

        // Create OTP
        $codeHash = hash('sha256', $otpCode . env('APP_KEY'));
        OtpCode::create([
            'mobile_number' => $mobileNumber,
            'code_hash' => $codeHash,
            'purpose' => OtpCode::PURPOSE_REGISTRATION,
            'expires_at' => now()->addMinutes(5),
            'is_used' => false,
            'attempts' => 0,
        ]);

        $response = $this->json('POST', '/api/v1/auth/register', [
            'mobile_number' => $mobileNumber,
            'otp_code' => $otpCode,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'national_id' => '1234567890',
            'password' => 'TestPassword123',
            'password_confirmation' => 'DifferentPassword',
        ]);

        $response->assertResponseStatus(422);
    }

    public function test_register_prevents_duplicate_national_id()
    {
        // Create existing user
        User::create([
            'mobile_number' => '9999999999',
            'first_name' => 'Existing',
            'last_name' => 'User',
            'national_id' => '1234567890',
            'password' => Hash::make('Password123'),
            'is_verified' => true,
            'mobile_verified_at' => now(),
        ]);

        $mobileNumber = '9145813194';
        $otpCode = '123456';

        // Create OTP
        $codeHash = hash('sha256', $otpCode . env('APP_KEY'));
        OtpCode::create([
            'mobile_number' => $mobileNumber,
            'code_hash' => $codeHash,
            'purpose' => OtpCode::PURPOSE_REGISTRATION,
            'expires_at' => now()->addMinutes(5),
            'is_used' => false,
            'attempts' => 0,
        ]);

        $response = $this->json('POST', '/api/v1/auth/register', [
            'mobile_number' => $mobileNumber,
            'otp_code' => $otpCode,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'national_id' => '1234567890', // Duplicate
            'password' => 'TestPassword123',
            'password_confirmation' => 'TestPassword123',
        ]);

        $response->assertResponseStatus(400);
        $data = json_decode($response->response->getContent(), true);
        $this->assertStringContainsString('National ID already registered', $data['message']);
    }
}

