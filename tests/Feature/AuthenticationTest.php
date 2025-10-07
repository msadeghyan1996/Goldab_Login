<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class AuthenticationTest extends TestCase
{
    public function test_check_mobile_endpoint_returns_exists_false_for_new_number()
    {
        $response = $this->json('POST', '/api/v1/auth/check-mobile', [
            'mobile_number' => '9145813194',
        ]);

        $response->assertResponseStatus(200);
        $response->seeJsonStructure([
            'success',
            'message',
            'data' => [
                'exists',
                'requires_password',
                'requires_registration',
            ],
        ]);
        
        $data = json_decode($response->response->getContent(), true);
        $this->assertFalse($data['data']['exists']);
        $this->assertTrue($data['data']['requires_registration']);
    }

    public function test_check_mobile_endpoint_returns_exists_true_for_registered_number()
    {
        // Create a user
        $user = User::create([
            'mobile_number' => '9145813194',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'national_id' => '1234567890',
            'password' => Hash::make('TestPassword123'),
            'is_verified' => true,
            'mobile_verified_at' => now(),
        ]);

        $response = $this->json('POST', '/api/v1/auth/check-mobile', [
            'mobile_number' => '9145813194',
        ]);

        $response->assertResponseStatus(200);
        
        $data = json_decode($response->response->getContent(), true);
        $this->assertTrue($data['data']['exists']);
        $this->assertTrue($data['data']['requires_password']);
    }

    public function test_check_mobile_validates_mobile_number_format()
    {
        $response = $this->json('POST', '/api/v1/auth/check-mobile', [
            'mobile_number' => 'invalid',
        ]);

        $response->assertResponseStatus(422);
        $response->seeJson([
            'success' => false,
        ]);
    }

    public function test_login_succeeds_with_valid_credentials()
    {
        // Create a user
        $user = User::create([
            'mobile_number' => '9145813194',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'national_id' => '1234567890',
            'password' => Hash::make('TestPassword123'),
            'is_verified' => true,
            'mobile_verified_at' => now(),
        ]);

        $response = $this->json('POST', '/api/v1/auth/login', [
            'mobile_number' => '9145813194',
            'password' => 'TestPassword123',
        ]);

        $response->assertResponseStatus(200);
        $response->seeJsonStructure([
            'success',
            'message',
            'data' => [
                'user',
                'token',
            ],
        ]);
        
        $data = json_decode($response->response->getContent(), true);
        $this->assertEquals('9145813194', $data['data']['user']['mobile_number']);
        $this->assertNotEmpty($data['data']['token']);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        // Create a user
        $user = User::create([
            'mobile_number' => '9145813194',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'national_id' => '1234567890',
            'password' => Hash::make('TestPassword123'),
            'is_verified' => true,
            'mobile_verified_at' => now(),
        ]);

        $response = $this->json('POST', '/api/v1/auth/login', [
            'mobile_number' => '9145813194',
            'password' => 'WrongPassword',
        ]);

        $response->assertResponseStatus(401);
        $response->seeJson([
            'success' => false,
        ]);
    }

    public function test_login_validates_required_fields()
    {
        $response = $this->json('POST', '/api/v1/auth/login', [
            'mobile_number' => '9145813194',
        ]);

        $response->assertResponseStatus(422);
    }

    public function test_me_endpoint_returns_authenticated_user()
    {
        // Create a user
        $user = User::create([
            'mobile_number' => '9145813194',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'national_id' => '1234567890',
            'password' => Hash::make('TestPassword123'),
            'is_verified' => true,
            'mobile_verified_at' => now(),
        ]);

        $response = $this->authenticatedJson('GET', '/api/v1/auth/me', [], $user->id);

        $response->assertResponseStatus(200);
        $response->seeJsonStructure([
            'success',
            'message',
            'data',
        ]);
        
        $data = json_decode($response->response->getContent(), true);
        $this->assertEquals('9145813194', $data['data']['mobile_number']);
        $this->assertEquals('John', $data['data']['first_name']);
    }

    public function test_me_endpoint_fails_without_authentication()
    {
        $response = $this->json('GET', '/api/v1/auth/me');

        $response->assertResponseStatus(401);
    }

    public function test_me_endpoint_fails_with_invalid_token()
    {
        $response = $this->json('GET', '/api/v1/auth/me', [], [
            'Authorization' => 'Bearer invalid_token',
        ]);

        $response->assertResponseStatus(401);
    }
}

