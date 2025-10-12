<?php

use App\Domain\Auth\Jobs\IssueOtpJob;
use App\Domain\Auth\Models\LoginAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Tests\Support\InMemoryRedisFactory;
use Illuminate\Contracts\Redis\Factory as RedisFactory;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->singleton(RedisFactory::class, fn () => new InMemoryRedisFactory);

    config()->set('otp.store', 'redis');
    config()->set('otp.sender', 'null');
    config()->set('queue.default', 'sync');
    config()->set('cache.default', 'array');
    Cache::store()->clear();
});

it('normalizes persian digits in otp authentication flow', function () {
    Bus::fake();

    $persianMobile = '۰۹۱۲۳۴۵۶۷۸۹'; // Persian digits for 09123456789

    $this->postJson('/api/v1/auth/request', ['mobile' => $persianMobile])
        ->assertOk()
        ->assertJsonPath('next', 'otp');

    $code = null;

    Bus::assertDispatched(IssueOtpJob::class, function (IssueOtpJob $job) use (&$code): bool {
        expect($job->context->mobile)->toBe('09123456789'); // normalized
        $code = $job->code;

        return true;
    });

    $verify = $this->postJson('/api/v1/auth/verify-otp', [
        'mobile' => $persianMobile,
        'otp' => $code,
    ]);

    $verify->assertOk()
        ->assertJsonPath('status', 'pending_profile');

    $pendingToken = $verify->json('token');

    $this->assertDatabaseHas('users', ['mobile' => '09123456789']); // normalized
    expect(LoginAttempt::query()->count())->toBe(2);

    $persianNationalId = '۱۲۳۴۵۶۷۸۹۱'; // Persian digits for valid national ID

    $profile = $this->withToken($pendingToken)->postJson('/api/v1/auth/complete-profile', [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'national_id' => $persianNationalId,
        'password' => '123@Secret',
    ]);

    $profile->assertOk()
        ->assertJsonPath('status', 'ok');

    $accessToken = $profile->json('token');

    $this->withToken($accessToken)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.user.national_id', '1234567891'); // normalized
});
