<?php

use App\Models\User;

test('phone number must be valid e164 format', function () {
    $response = $this->postJson('/api/auth/register/otp/request', [
        'phone_number' => 'not-a-phone',
    ]);

    $response->assertUnprocessable()
        ->assertJson([
            'success' => false,
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => [
                'phone_number',
            ],
        ]);
});


test('phone number are required', function () {
    $response = $this->postJson('/api/auth/register/otp/request', []);

    $response->assertUnprocessable()
        ->assertJson([
            'success' => false,
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => [
                'phone_number',
            ],
        ]);
});

test('Users who has password cannot request registration OTP', function () {
    $phoneNumber = fake()->e164PhoneNumber();

    User::factory()->create([
        'phone_number' => $phoneNumber,
        "password" => "password"
    ]);

    $response = $this->post('/api/auth/register/otp/request', [
        'phone_number' => $phoneNumber
    ]);

    $response
        ->assertForbidden()
        ->assertJson([
            'success' => false,
            'error_type' => 'password_authentication_required',
        ]);
});

it('User can request registration via OTP when phone number is new', function () {
    $phoneNumber = fake()->e164PhoneNumber();

    $response = $this->post('/api/auth/register/otp/request', [
        'phone_number' => $phoneNumber
    ]);

    $response->assertJson([
        'success' => true,
    ])->assertJsonStructure([
        'success',
        'message',
    ]);
});
