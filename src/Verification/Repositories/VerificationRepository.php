<?php

namespace Src\Verification\Repositories;

use App\Models\User;
use App\Models\Verification;
use App\Services\SmsService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Src\Verification\Contracts\VerificationContract;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class VerificationRepository implements VerificationContract {

    public function create(
        string $phone,
        ?string $password = null
    ): array {

        $user = User::query()->where('phone', $phone)->first();

        if ($user) {
            if (!$password) {
                return [
                    'message' => 'این شماره قبلاً ثبت‌نام کرده است. لطفاً رمز عبور خود را وارد کنید.',
                    'status'  => 'need_password'
                ];
            }

            if (!Hash::check($password, $user->password)) {
                return [
                    'message' => 'رمز عبور اشتباه است.',
                    'status'  => 'error'
                ];
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'message' => 'ورود با موفقیت انجام شد.',
                'status'  => 'success',
                'token'   => $token,
                'user'    => $user
            ];
        }

        $maxRetry = config('verification.max_otp_retry_count', 3);
        $blockSeconds = config('verification.block_seconds', 120);
        $codeExpireSeconds = config('verification.code_expire_seconds', 180);

        $verification = Verification::query()->where('phone', $phone)->first();

        if ($verification) {
            if ($verification->otp_retry_count >= $maxRetry) {
                if (now()->lessThan($verification->expire_at)) {
                    return [
                        'message' => 'شما بیش از حد مجاز درخواست کد کرده‌اید. لطفاً بعد از مدتی دوباره تلاش کنید.',
                        'status' => 429,
                    ];
                }
                $verification->delete();
                $verification = null;
            }
        }

        $verifyCode = helper()->generateVerifyCode();
        $hashedCode = Hash::make($verifyCode);
        logger()->info("OTP Code: " . $verifyCode . " | " . $hashedCode);

        if ($verification) {
            $verification['code'] = $hashedCode;
            ++$verification->otp_retry_count;
            $verification->expire_at = now()->addSeconds($codeExpireSeconds);

            if ($verification->otp_retry_count >= $maxRetry) {
                $verification->expire_at = now()->addSeconds($blockSeconds);
            }

            $verification->update();

            return [
                'totalRetry' => $verification->otp_retry_count,
                'phone' => $verification['phone'],
                'status' => 'Updated',
                'code' => $verifyCode
            ];
        }

        $verification = Verification::query()->create([
            'phone' => $phone,
            'code' => $hashedCode,
            'status' => false,
            'user_agent' => $this->setAgentHeader(),
            'otp_attempts' => 0,
            'otp_retry_count' => 1,
            'expire_at' => now()->addSeconds($codeExpireSeconds),
        ]);

        return [
            'message' => 'کد تایید ارسال گردید.',
            'phone' => $verification['phone'],
            'status' => 'Created',
            'code' => $verifyCode
        ];
    }

    private function setAgentHeader(): array|string|null {
        return request()->headers->get('User-Agent');
    }

    public function verify(string $code, string $phone): array|string {
        $verification = Verification::query()->where('phone', $phone)->first();

        if (!$verification) {
            return [
                "message" => "درخواستی برای این شماره ثبت نشده است.",
                'status' => 'error'
            ];
        }

        if (now()->greaterThan($verification->expire_at)) {
            $verification->delete();
            return [
                "message" => "کد منقضی شده است. لطفاً دوباره درخواست ارسال کد کنید.",
                'status' => 'error'
            ];
        }

        if ($verification->otp_attempts >= config('verification.max_otp_attempts', 3)) {
            return [
                "message" => "تعداد تلاش‌های ناموفق شما به حد مجاز رسیده است.",
                'status' => 'error'
            ];
        }

        if (!Hash::check($code, $verification['code'])) {
            $verification->increment('otp_attempts');
            return [
                "message" => "کد وارد شده نادرست است.",
                "status" => 'error',
            ];
        }

        $verification->delete();

        $user = User::query()->where('phone', $phone)->first();

        if (!$user) {
            $generatePass = Hash::make("pass123");
            $user = User::query()->create([
                'phone'    => $phone,
                'password' => $generatePass, // for dev mode
            ]);

            $tempToken = $user->createToken('temp_profile_token')->plainTextToken;

            logger()->info("Check Result: " . $generatePass . "pass123");

            return [
                'message' => 'OTP تایید شد. لطفاً پروفایل و رمز عبور خود را تکمیل کنید.',
                'status'  => 'incomplete',
                'user_id' => $user->id,
                'token'   => $tempToken,
            ];

        }

        return [
            'message' => 'این شماره قبلاً ثبت‌نام کرده است. لطفاً با رمز عبور وارد شوید.',
            'status'  => 'exists'
        ];
    }
}
