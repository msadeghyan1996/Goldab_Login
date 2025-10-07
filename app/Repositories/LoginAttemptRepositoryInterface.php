<?php
namespace App\Repositories;
use App\Models\LoginAttempt;
interface LoginAttemptRepositoryInterface extends BaseRepositoryInterface
{
    public function logAttempt(string $mobileNumber, string $ipAddress, bool $successful, ?string $userAgent = null): LoginAttempt;
    public function countRecentFailedByMobile(string $mobileNumber, int $minutes = 15): int;
    public function countRecentFailedByIp(string $ipAddress, int $minutes = 15): int;
    public function isAccountLocked(string $mobileNumber, int $maxAttempts = 5, int $lockoutMinutes = 15): bool;
}