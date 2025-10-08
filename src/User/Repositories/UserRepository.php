<?php

namespace Src\User\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Src\User\Contracts\UserContract;

final class UserRepository implements UserContract {

    public function completeProfile(
        int $userId,
        array $data
    ): array {

        $user = User::query()->find($userId);

        if (!$user) {
            return [
                'message' => 'کاربر یافت نشد.',
                'status'  => 'error'
            ];
        }

        $user->update([
            'first_name'   => $data['first_name'],
            'last_name'    => $data['last_name'],
            'national_id'  => $data['national_id'],
            'password'     => Hash::make($data['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'message' => 'پروفایل با موفقیت تکمیل شد.',
            'status'  => 'success',
            'token'   => $token,
            'user'    => $user
        ];
    }
}
