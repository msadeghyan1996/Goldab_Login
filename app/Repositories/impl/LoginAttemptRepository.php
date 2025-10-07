<?php
namespace App\Repositories\impl;
use App\Models\LoginAttempt;
use App\Repositories\LoginAttemptRepositoryInterface;
class LoginAttemptRepository extends BaseRepository implements LoginAttemptRepositoryInterface
{
    public function __construct(LoginAttempt $model)
    {
        parent::__construct($model);
    }
    public function logAttempt(string $mobileNumber, string $ipAddress, bool $successful, ?string $userAgent = null): LoginAttempt
    {
        return $this->create([
            'mobile_number' => $mobileNumber,
            'ip_address' => $ipAddress,
            'successful' => $successful,
            'user_agent' => $userAgent,
        ]);
    }
    public function countRecentFailedByMobile(string $mobileNumber, int $minutes = 15): int
    {
        return $this->model
            ->forMobile($mobileNumber)
            ->failed()
            ->recent($minutes)
            ->count();
    }
    public function countRecentFailedByIp(string $ipAddress, int $minutes = 15): int
    {
        return $this->model
            ->forIp($ipAddress)
            ->failed()
            ->recent($minutes)
            ->count();
    }
    public function isAccountLocked(string $mobileNumber, int $maxAttempts = 5, int $lockoutMinutes = 15): bool
    {
        $failedAttempts = $this->countRecentFailedByMobile($mobileNumber, $lockoutMinutes);
        return $failedAttempts >= $maxAttempts;
    }
}