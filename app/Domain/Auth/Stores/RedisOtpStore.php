<?php

namespace App\Domain\Auth\Stores;

use App\Domain\Auth\Contracts\OtpStore;
use App\Domain\Auth\DTO\StoredOtpData;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Support\Str;
use JsonException;

class RedisOtpStore implements OtpStore
{
    public function __construct(
        private readonly RedisFactory $redis,
        private readonly string $connection = 'default',
        private readonly string $keyPrefix = 'otp:',
    ) {}

    public function put(string $mobile, string $codeHash, DateTimeInterface $expiresAt): void
    {
        $ttl = $this->secondsUntil($expiresAt);
        $payload = json_encode([
            'code_hmac' => $codeHash,
            'expires_at' => $expiresAt->getTimestamp(),
        ], JSON_THROW_ON_ERROR);

        $connection = $this->connection();

        $connection->setex($this->codeKey($mobile), $ttl, $payload);
        $connection->setex($this->attemptsKey($mobile), $ttl, '0');
        $connection->del($this->lockKey($mobile));
    }

    public function get(string $mobile): ?StoredOtpData
    {
        $connection = $this->connection();
        $lockRaw = $connection->get($this->lockKey($mobile));
        $lockedUntil = $lockRaw !== null
            ? CarbonImmutable::createFromTimestampUTC((int) $lockRaw)
            : null;

        $value = $connection->get($this->codeKey($mobile));

        if ($value === null) {
            if ($lockedUntil !== null) {
                return new StoredOtpData(
                    $mobile,
                    '',
                    CarbonImmutable::now(),
                    (int) ($connection->get($this->attemptsKey($mobile)) ?? 0),
                    $lockedUntil,
                );
            }

            return null;
        }

        try {
            $decoded = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->clear($mobile);

            return null;
        }

        $expiresAt = CarbonImmutable::createFromTimestampUTC((int) $decoded['expires_at']);
        $attempts = (int) ($connection->get($this->attemptsKey($mobile)) ?? 0);

        return new StoredOtpData($mobile, (string) $decoded['code_hmac'], $expiresAt, $attempts, $lockedUntil);
    }

    public function clear(string $mobile): void
    {
        $this->connection()->del([
            $this->codeKey($mobile),
            $this->attemptsKey($mobile),
            $this->lockKey($mobile),
        ]);
    }

    public function incrementAttempts(string $mobile): int
    {
        $connection = $this->connection();
        $attempts = (int) $connection->incr($this->attemptsKey($mobile));
        $codeTtl = (int) $connection->ttl($this->codeKey($mobile));

        if ($codeTtl > 0) {
            $connection->expire($this->attemptsKey($mobile), $codeTtl);
        }

        return $attempts;
    }

    public function lock(string $mobile, DateTimeInterface $lockedUntil): void
    {
        $ttl = $this->secondsUntil($lockedUntil);
        $this->connection()->setex(
            $this->lockKey($mobile),
            $ttl,
            (string) $lockedUntil->getTimestamp(),
        );
    }

    private function connection()
    {
        return $this->redis->connection($this->connection);
    }

    private function codeKey(string $mobile): string
    {
        return $this->keyPrefix.'code:'.$this->normalizeMobile($mobile);
    }

    private function attemptsKey(string $mobile): string
    {
        return $this->keyPrefix.'attempts:'.$this->normalizeMobile($mobile);
    }

    private function lockKey(string $mobile): string
    {
        return $this->keyPrefix.'lock:'.$this->normalizeMobile($mobile);
    }

    private function normalizeMobile(string $mobile): string
    {
        return Str::of($mobile)->replace(['+', ' '], '')->value();
    }

    private function secondsUntil(DateTimeInterface $moment): int
    {
        $seconds = $moment->getTimestamp() - time();

        return max(1, $seconds);
    }
}
