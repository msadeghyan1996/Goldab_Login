<?php

use App\Domain\Auth\Jobs\IssueOtpJob;
use App\Domain\Auth\Models\LoginAttempt;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Support\InMemoryRedisFactory;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->singleton(RedisFactory::class, fn () => new InMemoryRedisFactory);

    config()->set('otp.store', 'redis');
    config()->set('otp.sender', 'null');
    config()->set('queue.default', 'sync');
    config()->set('cache.default', 'array');
    Cache::store()->clear();
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});


it('completes the otp authentication flow', function () {
    Bus::fake();

    $mobile = '09123456789';

    $this->postJson('/api/v1/auth/request', ['mobile' => $mobile])
        ->assertOk()
        ->assertJsonPath('next', 'otp');

    $code = null;

    Bus::assertDispatched(IssueOtpJob::class, function (IssueOtpJob $job) use (&$code, $mobile): bool {
        expect($job->context->mobile)->toBe($mobile);
        $code = $job->code;

        return true;
    });

    $verify = $this->postJson('/api/v1/auth/verify-otp', [
        'mobile' => $mobile,
        'otp' => $code,
    ]);

    $verify->assertOk()
        ->assertJsonPath('status', 'pending_profile');

    $pendingToken = $verify->json('token');

    $this->assertDatabaseHas('users', ['mobile' => $mobile]);
    expect(LoginAttempt::query()->count())->toBe(2);

    $profile = $this->withToken($pendingToken)->postJson('/api/v1/auth/complete-profile', [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'national_id' => validNationalId(),
        'password' => '123@Secret',
    ]);

    $profile->assertOk()
        ->assertJsonPath('status', 'ok');

    $accessToken = $profile->json('token');

    $this->withToken($accessToken)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.user.has_completed_profile', true);

    $this->withToken($accessToken)
        ->postJson('/api/v1/auth/logout')
        ->assertOk();
});

it('prevents verification of expired otps', function () {
    Bus::fake();

    $mobile = '09120001111';

    $this->postJson('/api/v1/auth/request', ['mobile' => $mobile])->assertOk();

    $code = null;

    Bus::assertDispatched(IssueOtpJob::class, function (IssueOtpJob $job) use (&$code): bool {
        $code = $job->code;

        return true;
    });

    CarbonImmutable::setTestNow(CarbonImmutable::now()->addMinutes(10));

    $this->postJson('/api/v1/auth/verify-otp', [
        'mobile' => $mobile,
        'otp' => $code,
    ])->assertStatus(422)
        ->assertJsonPath('status', 'expired');
});

it('locks verification after repeated failures', function () {
    Bus::fake();

    $mobile = '09120002222';

    $this->postJson('/api/v1/auth/request', ['mobile' => $mobile])->assertOk();

    $code = null;

    Bus::assertDispatched(IssueOtpJob::class, function (IssueOtpJob $job) use (&$code): bool {
        $code = $job->code;

        return true;
    });

    $limit = config('otp.attempt_limit', 5);

    for ($attempt = 0; $attempt < $limit - 1; $attempt++) {
        $this->postJson('/api/v1/auth/verify-otp', [
            'mobile' => $mobile,
            'otp' => '000000',
        ])->assertStatus(422);
    }

    $this->postJson('/api/v1/auth/verify-otp', [
        'mobile' => $mobile,
        'otp' => '000000',
    ])->assertStatus(423);

    $this->postJson('/api/v1/auth/verify-otp', [
        'mobile' => $mobile,
        'otp' => $code,
    ])->assertStatus(423)
        ->assertJsonPath('status', 'locked')
        ->assertJsonPath('locked_until', fn ($value) => ! empty($value));
});

it('rate limits otp requests per mobile and ip', function () {
    Bus::fake();

    $mobile = '09120003333';

    for ($i = 0; $i < 5; $i++) {
        $response = $this->postJson('/api/v1/auth/request', ['mobile' => $mobile]);
        $response->assertStatus(200);
    }

    $this->postJson('/api/v1/auth/request', ['mobile' => $mobile])
        ->assertStatus(429);

    RateLimiter::clear(sha1('127.0.0.1|'.$mobile));
});

it('directs existing users with passwords to the password flow', function () {
    Bus::fake();

    $user = User::factory()->create(['mobile' => '09130000000']);

    $this->postJson('/api/v1/auth/request', ['mobile' => $user->mobile])
        ->assertOk()
        ->assertJsonPath('next', 'password');

    Bus::assertNotDispatched(IssueOtpJob::class);
});

it('rejects otp verification when a password is already set', function () {
    $user = User::factory()->create(['mobile' => '09130000001']);

    $this->postJson('/api/v1/auth/verify-otp', [
        'mobile' => $user->mobile,
        'otp' => '123456',
    ])->assertStatus(422)
        ->assertJsonPath('status', 'invalid');
});
