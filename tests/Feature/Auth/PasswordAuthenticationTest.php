<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('user with password can login using password', function () {
    $phoneNumber = fake()->e164PhoneNumber();
    $password = fake()->password(8);

    User::factory()->create([
        'phone_number' => $phoneNumber,
        'password' => Hash::make($password),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'phone_number' => $phoneNumber,
        'password' => $password,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'success',
            'data' => [
                'access_token',
                'token_type',
            ],
        ]);
});

test('user with wrong password is rejected', function () {
    $phoneNumber = fake()->e164PhoneNumber();
    $password = fake()->password(8);

    User::factory()->create([
        'phone_number' => $phoneNumber,
        'password' => Hash::make($password),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'phone_number' => $phoneNumber,
        'password' => fake()->password(8),
    ]);

    $response->assertUnauthorized()
        ->assertJson([
            'success' => false,
        ])
        ->assertJsonStructure([
            'success',
            'message',
        ]);
});

test('phone number must be valid e164 format', function () {
    $response = $this->postJson('/api/auth/login', [
        'phone_number' => 'not-a-phone',
        'password' => fake()->password(8),
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

test('phone number and password are required', function () {
    $response = $this->postJson('/api/auth/login', []);

    $response->assertUnprocessable()
        ->assertJson([
            'success' => false,
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => [
                'phone_number',
                'password',
            ],
        ]);
});
