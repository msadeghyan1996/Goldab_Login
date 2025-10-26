<?php

use App\Events\OtpCodeGenerated;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

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
    ]);

    Event::fake([OtpCodeGenerated::class]);

    Carbon::setTestNow(Carbon::create(2025, 1, 1, 12));
});

afterEach(function () {
    Carbon::setTestNow();
});

it('stores hashed otp payload in redis with ttl', function () {
    $phoneNumber = fake()->e164PhoneNumber();

    $response = $this->postJson('/api/auth/register/otp/request', [
        'phone_number' => $phoneNumber,
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJson(['success' => true]);

    $dispatched = Event::dispatched(OtpCodeGenerated::class);
    expect($dispatched)->toHaveCount(1);

    $event = $dispatched[0][0];
    expect($event->phoneNumber)->toBe($phoneNumber);

    $cacheKey = "otp:{$phoneNumber}";
    $payload = Cache::get($cacheKey);

    expect($payload)->not->toBeNull()
        ->and($payload)->toHaveKeys(['hash', 'attempts', 'expires_at'])
        ->and($payload['attempts'])->toBe(0)
        ->and($payload['expires_at'])->toBeInstanceOf(Carbon::class)
        ->and($payload['expires_at']->greaterThan(Carbon::now()))->toBeTrue()
        ->and(Hash::check($event->code, $payload['hash']))->toBeTrue();
});

it('invalidates previous otp when a new one is generated', function () {
    $phoneNumber = fake()->e164PhoneNumber();

    $this->postJson('/api/auth/register/otp/request', [
        'phone_number' => $phoneNumber,
    ]);

    $firstEvent = Event::dispatched(OtpCodeGenerated::class)[0][0];
    $firstCode = $firstEvent->code;

    Event::fake([OtpCodeGenerated::class]);

    $this->postJson('/api/auth/register/otp/request', [
        'phone_number' => $phoneNumber,
    ]);

    $secondEvent = Event::dispatched(OtpCodeGenerated::class)[0][0];
    $secondCode = $secondEvent->code;
    expect($secondCode)->not->toBe($firstCode);

    $invalidResponse = $this->postJson('/api/auth/register/otp/verify', [
        'phone_number' => $phoneNumber,
        'code' => $firstCode,
    ]);

    $invalidResponse->assertStatus(422)
        ->assertJson([
            'success' => false,
            'error_type' => 'invalid_code',
        ]);

    $validResponse = $this->postJson('/api/auth/register/otp/verify', [
        'phone_number' => $phoneNumber,
        'code' => $secondCode,
    ]);

    $validResponse->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'registration_token',
            ],
        ]);
});

it('returns a registration token when verifying a new user', function () {
    $phoneNumber = fake()->e164PhoneNumber();

    $this->postJson('/api/auth/register/otp/request', [
        'phone_number' => $phoneNumber,
    ]);

    $event = Event::dispatched(OtpCodeGenerated::class)[0][0];

    $response = $this->postJson('/api/auth/register/otp/verify', [
        'phone_number' => $phoneNumber,
        'code' => $event->code,
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'registration_token',
            ],
        ])
        ->assertJson([
            'success' => true,
        ]);

    $registrationToken = $response->json('data.registration_token');
    expect($registrationToken)->not->toBeEmpty();

    $pendingKey = "pending-registration:{$registrationToken}";
    $pending = Cache::get($pendingKey);

    expect($pending)->toMatchArray([
        'phone_number' => $phoneNumber,
    ])->and($pending['verified_at'])->toBeInstanceOf(Carbon::class)
        ->and($pending['verified_at']->equalTo(Carbon::now()))->toBeTrue()
        ->and(Cache::has("otp:{$phoneNumber}"))->toBeFalse();
});

it('increments attempt counter for invalid codes', function () {
    $phoneNumber = fake()->e164PhoneNumber();

    $this->postJson('/api/auth/register/otp/request', [
        'phone_number' => $phoneNumber,
    ]);

    $response = $this->postJson('/api/auth/register/otp/verify', [
        'phone_number' => $phoneNumber,
        'code' => '000000',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'error_type' => 'invalid_code',
        ]);

    $payload = Cache::get("otp:{$phoneNumber}");
    expect($payload['attempts'])->toBe(1);
});

it('locks the otp after too many failed attempts', function () {
    $phoneNumber = fake()->e164PhoneNumber();

    $this->postJson('/api/auth/register/otp/request', [
        'phone_number' => $phoneNumber,
    ]);

    foreach (range(1, 5) as $attempt) {
        $response = $this->postJson('/api/auth/register/otp/verify', [
            'phone_number' => $phoneNumber,
            'code' => '000000',
        ]);

        if ($attempt < 5) {
            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'error_type' => 'invalid_code',
                ]);
        } else {
            $response->assertStatus(429)
                ->assertJson([
                    'success' => false,
                    'error_type' => 'too_many_attempts',
                ]);
        }
    }

    expect(Cache::has("otp:{$phoneNumber}"))->toBeFalse();
});

it('rejects expired otps', function () {
    $phoneNumber = fake()->e164PhoneNumber();

    Event::fake([OtpCodeGenerated::class]);

    $this->postJson('/api/auth/register/otp/request', [
        'phone_number' => $phoneNumber,
    ]);

    $event = Event::dispatched(OtpCodeGenerated::class)[0][0];

    Carbon::setTestNow(Carbon::now()->addMinutes(6));

    $response = $this->postJson('/api/auth/register/otp/verify', [
        'phone_number' => $phoneNumber,
        'code' => $event->code,
    ]);

    $response->assertStatus(410)
        ->assertJson([
            'success' => false,
            'error_type' => 'otp_expired',
        ]);

    expect(Cache::has("otp:{$phoneNumber}"))->toBeFalse();
});

it('rate limits otp requests per phone number', function () {
    $phoneNumber = fake()->e164PhoneNumber();

    foreach (range(1, 3) as $i) {
        $response = $this->postJson('/api/auth/register/otp/request', [
            'phone_number' => $phoneNumber,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);
    }

    $response = $this->postJson('/api/auth/register/otp/request', [
        'phone_number' => $phoneNumber,
    ]);

    $response->assertStatus(429)
        ->assertJson([
            'success' => false,
            'error_type' => 'too_many_requests',
        ]);
});
