<?php

use App\Domain\Auth\Contracts\OtpStore;
use App\Domain\Auth\Stores\RedisOtpStore;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InMemoryRedisFactory;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

dataset('otpStores', [
    'redis' => fn () => new RedisOtpStore(new InMemoryRedisFactory),
]);

it('stores and retrieves otp payloads', function (callable $factory) {
    /** @var OtpStore $store */
    $store = $factory();
    $expiresAt = CarbonImmutable::now()->addMinutes(5);

    $store->put('09123456789', 'hash-value', $expiresAt);

    $data = $store->get('09123456789');

    expect($data)
        ->not->toBeNull()
        ->and($data->codeHash)->toBe('hash-value')
        ->and($data->expiresAt->getTimestamp())->toBe($expiresAt->getTimestamp())
        ->and($data->attempts)->toBe(0);
})->with('otpStores');

it('increments attempts', function (callable $factory) {
    $store = $factory();
    $expiresAt = CarbonImmutable::now()->addMinutes(5);

    $store->put('09123456789', 'hash', $expiresAt);

    expect($store->incrementAttempts('09123456789'))->toBe(1)
        ->and($store->incrementAttempts('09123456789'))->toBe(2);

    $data = $store->get('09123456789');

    expect($data?->attempts)->toBe(2);
})->with('otpStores');

it('clears stored otp data', function (callable $factory) {
    $store = $factory();
    $expiresAt = CarbonImmutable::now()->addMinutes(5);

    $store->put('09123456789', 'hash', $expiresAt);
    $store->incrementAttempts('09123456789');

    $store->clear('09123456789');

    expect($store->get('09123456789'))->toBeNull();
})->with('otpStores');

it('stores lock information', function (callable $factory) {
    $store = $factory();
    $expiresAt = CarbonImmutable::now()->addMinutes(5);
    $store->put('09123456789', 'hash', $expiresAt);

    $lockedUntil = CarbonImmutable::now()->addMinutes(15);
    $store->lock('09123456789', $lockedUntil);

    $data = $store->get('09123456789');

    expect($data?->lockedUntil?->greaterThan(CarbonImmutable::now()))->toBeTrue();
})->with('otpStores');

it('resets attempts when a new code is stored', function (callable $factory) {
    $store = $factory();
    $expiresAt = CarbonImmutable::now()->addMinutes(5);

    $store->put('09123456789', 'hash', $expiresAt);
    $store->incrementAttempts('09123456789');

    $store->put('09123456789', 'new-hash', CarbonImmutable::now()->addMinutes(5));

    $data = $store->get('09123456789');

    expect($data?->attempts)->toBe(0)
        ->and($data?->codeHash)->toBe('new-hash');
})->with('otpStores');
