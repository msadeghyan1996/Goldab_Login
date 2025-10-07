<?php
namespace App\Repositories;
use App\Models\User;
interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function findByMobileNumber(string $mobileNumber): ?User;
    public function findByNationalId(string $nationalId): ?User;
    public function createWithMobile(string $mobileNumber): User;
    public function updateProfile(int $userId, array $profileData): bool;
    public function mobileExists(string $mobileNumber): bool;
    public function nationalIdExists(string $nationalId, ?int $excludeUserId = null): bool;
}