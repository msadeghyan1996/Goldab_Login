<?php

namespace App\Domain\Auth\Contracts;

use App\Domain\Auth\DTO\StoredOtpData;
use DateTimeInterface;

interface OtpStore
{
    public function put(string $mobile, string $codeHash, DateTimeInterface $expiresAt): void;

    public function get(string $mobile): ?StoredOtpData;

    public function clear(string $mobile): void;

    public function incrementAttempts(string $mobile): int;

    public function lock(string $mobile, DateTimeInterface $lockedUntil): void;
}
