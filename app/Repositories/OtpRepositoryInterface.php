<?php
namespace App\Repositories;
use App\Models\OtpCode;
interface OtpRepositoryInterface extends BaseRepositoryInterface
{
    public function createOtp(string $mobileNumber, string $codeHash, string $purpose, int $expirySeconds): OtpCode;
    public function findValidOtp(string $mobileNumber, string $purpose): ?OtpCode;
    public function invalidatePreviousOtps(string $mobileNumber, string $purpose): int;
    public function countRecentOtps(string $mobileNumber, int $minutes = 15): int;
    public function cleanupExpiredOtps(): int;
}