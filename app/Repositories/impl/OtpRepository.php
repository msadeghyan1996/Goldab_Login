<?php
namespace App\Repositories\impl;
use App\Models\OtpCode;
use App\Repositories\OtpRepositoryInterface;
use Illuminate\Support\Carbon;
class OtpRepository extends BaseRepository implements OtpRepositoryInterface
{
    public function __construct(OtpCode $model)
    {
        parent::__construct($model);
    }
    public function createOtp(string $mobileNumber, string $codeHash, string $purpose, int $expirySeconds): OtpCode
    {
        return $this->create([
            'mobile_number' => $mobileNumber,
            'code_hash' => $codeHash,
            'purpose' => $purpose,
            'expires_at' => Carbon::now()->addSeconds($expirySeconds),
            'attempts' => 0,
            'is_used' => false,
        ]);
    }
    public function findValidOtp(string $mobileNumber, string $purpose): ?OtpCode
    {
        return $this->model
            ->forMobile($mobileNumber)
            ->forPurpose($purpose)
            ->valid()
            ->orderBy('created_at', 'desc')
            ->first();
    }
    public function invalidatePreviousOtps(string $mobileNumber, string $purpose): int
    {
        return $this->model
            ->forMobile($mobileNumber)
            ->forPurpose($purpose)
            ->where('is_used', false)
            ->update(['is_used' => true]);
    }
    public function countRecentOtps(string $mobileNumber, int $minutes = 15): int
    {
        return $this->model
            ->forMobile($mobileNumber)
            ->where('created_at', '>', Carbon::now()->subMinutes($minutes))
            ->count();
    }
    public function cleanupExpiredOtps(): int
    {
        return $this->model
            ->where('expires_at', '<', Carbon::now()->subDays(1))
            ->delete();
    }
}