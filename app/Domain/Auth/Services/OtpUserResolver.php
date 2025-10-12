<?php

namespace App\Domain\Auth\Services;

use App\Models\User;

readonly class OtpUserResolver
{
    public function findExisting(string $mobile): ?User
    {
        return User::query()->where('mobile', $mobile)->first();
    }

    public function findOrCreate(string $mobile): User
    {
        return User::query()->firstOrCreate([
            'mobile' => $mobile,
        ]);
    }
}
