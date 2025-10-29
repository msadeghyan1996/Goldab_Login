<?php

use App\Events\OtpCodeGenerated;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

beforeEach(function () {
    config()->set('cache.default', 'array');

    config()->set('otp', [
        'code_length' => 6,
        'ttl_minutes' => 5,
        'max_attempts' => 5,
        'rate_limit' => [
            'per_phone_attempts' => 3,
            'per_phone_decay_seconds' => 60,
        ],
        'registration_token_ttl_minutes' => 15,
    ]);

    Event::fake([OtpCodeGenerated::class]);

    Carbon::setTestNow(Carbon::create(2025, 1, 1, 12));
});

afterEach(function () {
    Carbon::setTestNow();
});

it('completes registration with a valid registration token', function () {
    $phoneNumber = fake()->unique()->e164PhoneNumber();

    $registrationToken = generateRegistrationTokenFor($phoneNumber);

    $payload = [
        'registration_token' => $registrationToken,
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
        'national_id' => fake()->unique()->numerify(str_repeat('#', 10)),
        'password' => $password = fake()->password(12),
    ];

    $response = $this->postJson('/api/auth/register', $payload);

    $response->assertCreated()
        ->assertJsonStructure([
            'success',
            'data' => [
                'access_token',
                'token_type',
            ],
        ])
        ->assertJson([
            'success' => true,
            'data' => [
                'token_type' => 'Bearer',
            ],
        ]);

    $user = User::where('phone_number', $phoneNumber)->first();
    expect($user)->not->toBeNull()
        ->and($user->first_name)->toBe($payload['first_name'])
        ->and($user->last_name)->toBe($payload['last_name'])
        ->and($user->national_id)->toBe($payload['national_id'])
        ->and(Hash::check($password, $user->password))->toBeTrue();

    $this->assertDatabaseCount('personal_access_tokens', 1);
    expect(Cache::has("pending-registration:{$registrationToken}"))->toBeFalse();
});

it('rejects registration when the registration token is missing or expired', function () {
    $payload = [
        'registration_token' => Str::random(40),
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
        'national_id' => fake()->unique()->numerify(str_repeat('#', 10)),
        'password' => fake()->password(12),
    ];

    $response = $this->postJson('/api/auth/register', $payload);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'error_type' => 'invalid_registration_token',
        ]);
});

it('cannot reuse a registration token once consumed', function () {
    $phoneNumber = fake()->unique()->e164PhoneNumber();

    $registrationToken = generateRegistrationTokenFor($phoneNumber);

    $payload = [
        'registration_token' => $registrationToken,
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
        'national_id' => fake()->unique()->numerify(str_repeat('#', 10)),
        'password' => fake()->password(12),
    ];

    $this->postJson('/api/auth/register', $payload)->assertCreated();

    $response = $this->postJson('/api/auth/register', $payload);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'error_type' => 'invalid_registration_token',
        ]);
});

function generateRegistrationTokenFor(string $phoneNumber): string
{
    $requestResponse = test()->postJson('/api/auth/register/otp/request', [
        'phone_number' => $phoneNumber,
    ]);

    $requestResponse->assertOk();

    $event = Event::dispatched(OtpCodeGenerated::class)[0][0];

    $verifyResponse = test()->postJson('/api/auth/register/otp/verify', [
        'phone_number' => $phoneNumber,
        'code' => $event->code,
    ]);

    $verifyResponse->assertOk();

    return $verifyResponse->json('data.registration_token');
}
