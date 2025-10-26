<?php

use App\Models\User;
use Illuminate\Support\Facades\Log;

it('Leads to registration if the user does not exist', function () {
    $response = $this->post('/api/auth/lookup', [
        'phone_number' => fake()->e164PhoneNumber()
    ]);

    $response->assertJson([
        'success' => true,
        'data' => [
            'is_new_user' => true,
            'authentication_type' => 'otp'
        ]
    ]);
});

it('Leads to password authentication if user exist', function () {
    $user = User::factory()->create([
        'phone_number' => fake()->e164PhoneNumber(),
        "password" => "password"
    ]);

    $response = $this->post('/api/auth/lookup', [
        'phone_number' => $user->phone_number
    ]);

    $response->assertJson([
        'success' => true,
        'data' => [
            'is_new_user' => false,
            'authentication_type' => 'password',
        ]
    ]);
});
