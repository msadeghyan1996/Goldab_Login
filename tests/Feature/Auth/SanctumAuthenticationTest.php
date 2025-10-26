<?php

use App\Models\User;

it('denies unauthenticated access to profile endpoint', function () {
    $response = $this->getJson('/api/auth/me');

    $response->assertUnauthorized()
        ->assertJson([
            'success' => false,
            'message' => 'Unauthenticated.',
            'error_type' => 'unauthenticated',
        ]);
});

it('returns authenticated user data when token provided', function () {
    $user = User::factory()->create();

    $token = $user->createToken('mobile')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/auth/me');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'national_id' => $user->national_id,
                'phone_number' => $user->phone_number,
            ],
        ]);
});

it('revokes token on logout', function () {
    $user = User::factory()->create();

    $tokenResult = $user->createToken('mobile');
    $plainTextToken = $tokenResult->plainTextToken;
    $tokenId = $tokenResult->accessToken->id;

    $response = $this->withToken($plainTextToken)->postJson('/api/auth/logout');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);

    app('auth')->forgetGuards();

    $this->withToken($plainTextToken)
        ->getJson('/api/auth/me')
        ->assertUnauthorized()
        ->assertJson([
            'success' => false,
            'error_type' => 'unauthenticated',
        ]);
});

it('cant logout without authentication', function () {
    $response = $this->postJson('/api/auth/logout');

    $response->assertUnauthorized()
        ->assertJson([
            'success' => false,
        ])->assertJsonStructure([
            'message',
            'error_type',
        ]);
});
