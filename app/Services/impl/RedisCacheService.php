<?php
namespace App\Services\impl;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
class RedisCacheService extends BaseService
{
    public function storeOtp(string $mobileNumber, string $codeHash, string $purpose, int $ttlSeconds): bool
    {
        $key = $this->getOtpKey($mobileNumber, $purpose);
        $data = [
            'code_hash' => $codeHash,
            'mobile_number' => $mobileNumber,
            'purpose' => $purpose,
            'attempts' => 0,
            'created_at' => Carbon::now()->toIso8601String(),
            'expires_at' => Carbon::now()->addSeconds($ttlSeconds)->toIso8601String(),
        ];
        return Cache::put($key, $data, $ttlSeconds);
    }
    public function getOtp(string $mobileNumber, string $purpose): ?array
    {
        $key = $this->getOtpKey($mobileNumber, $purpose);
        return Cache::get($key);
    }
    public function incrementAttempts(string $mobileNumber, string $purpose): int
    {
        $key = $this->getOtpKey($mobileNumber, $purpose);
        $data = Cache::get($key);
        if (!$data) {
            return 0;
        }
        $data['attempts'] = ($data['attempts'] ?? 0) + 1;
        $expiresAt = Carbon::parse($data['expires_at']);
        $ttl = max(0, $expiresAt->diffInSeconds(Carbon::now()));
        Cache::put($key, $data, $ttl);
        return $data['attempts'];
    }
    public function deleteOtp(string $mobileNumber, string $purpose): bool
    {
        $key = $this->getOtpKey($mobileNumber, $purpose);
        return Cache::forget($key);
    }
    public function countRecentOtpRequests(string $mobileNumber, int $minutes = 15): int
    {
        $key = $this->getOtpCountKey($mobileNumber);
        return (int) Cache::get($key, 0);
    }
    public function incrementOtpRequestCount(string $mobileNumber, int $minutes = 15): int
    {
        $key = $this->getOtpCountKey($mobileNumber);
        $count = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $count, $minutes * 60);
        return $count;
    }
    public function storeRateLimit(string $identifier, int $ttlSeconds): void
    {
        $key = "rate_limit:{$identifier}";
        $count = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $count, $ttlSeconds);
    }
    public function getRateLimitCount(string $identifier): int
    {
        $key = "rate_limit:{$identifier}";
        return (int) Cache::get($key, 0);
    }
    public function storeUserSession(int $userId, array $data, int $ttlSeconds): bool
    {
        $key = "user_session:{$userId}";
        return Cache::put($key, $data, $ttlSeconds);
    }
    public function getUserSession(int $userId): ?array
    {
        $key = "user_session:{$userId}";
        return Cache::get($key);
    }
    public function deleteUserSession(int $userId): bool
    {
        $key = "user_session:{$userId}";
        return Cache::forget($key);
    }
    protected function getOtpKey(string $mobileNumber, string $purpose): string
    {
        return "otp:{$purpose}:{$mobileNumber}";
    }
    protected function getOtpCountKey(string $mobileNumber): string
    {
        return "otp_count:{$mobileNumber}";
    }
}