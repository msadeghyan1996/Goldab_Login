<?php
namespace App\Repositories\impl;
use App\Models\User;
use App\Repositories\UserRepositoryInterface;
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }
    public function findByMobileNumber(string $mobileNumber): ?User
    {
        return $this->model->where('mobile_number', $mobileNumber)->first();
    }
    public function findByNationalId(string $nationalId): ?User
    {
        return $this->model->where('national_id', $nationalId)->first();
    }
    public function createWithMobile(string $mobileNumber): User
    {
        return $this->create([
            'mobile_number' => $mobileNumber,
            'is_verified' => false,
        ]);
    }
    public function updateProfile(int $userId, array $profileData): bool
    {
        return $this->update($userId, $profileData);
    }
    public function mobileExists(string $mobileNumber): bool
    {
        return $this->model->where('mobile_number', $mobileNumber)->exists();
    }
    public function nationalIdExists(string $nationalId, ?int $excludeUserId = null): bool
    {
        $query = $this->model->where('national_id', $nationalId);
        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }
        return $query->exists();
    }
}