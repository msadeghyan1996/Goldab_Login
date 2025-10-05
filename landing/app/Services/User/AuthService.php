<?php

namespace App\Services\User;

use App\Models\User\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function findUserByPhone(string $phoneNumber): ?User
    {
        return User::query()->where('phone_number', $phoneNumber)->first();
    }

    public function verifyOTP(string $phoneNumber, string $otp): bool
    {
        $targetOtp = cache()->get(User::getOtpCodeCacheKeyByPhoneNumber($phoneNumber));
        $enteredOtp = convertToEnglishDigits($otp);
        return $targetOtp && $enteredOtp === (string)$targetOtp;
    }

    public function completeFields(User $user, array $data): User
    {
        $user->update($data);
        return $user->refresh();
    }

    public function loginWithPassword(string $phoneNumber, string $password): ?User
    {
        $user = User::query()->where('phone_number', $phoneNumber)->first();
        if (!$user) {
            return null;
        }
        return Hash::check($password, $user->password) ? $user : null;
    }
}
