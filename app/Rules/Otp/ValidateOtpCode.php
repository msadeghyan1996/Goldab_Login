<?php

namespace App\Rules\Otp;

use App\Services\Otp\OtpCache;
use App\Services\Otp\VerifyOtpService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateOtpCode implements ValidationRule
{
    public function __construct(private readonly string $phone)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $otpToken = OtpCache::get($this->phone);

        if (!$otpToken) {
            $fail(trans('otp.code_not_exists'));

            return;
        }

        if (
            strcmp($otpToken->request_ip, request()->ip()) !== 0
            || strcmp($otpToken->device_id ?? '', request()->header('X-Device-Id', '')) !== 0
        ) {
            $fail(trans('otp.ip_mismatch'));

            return;
        }

        $verifyOtpService = app(VerifyOtpService::class);
        $otpToken = $verifyOtpService->setPhone($this->phone)->getOtpToken();

        $verifyOtpService->attempt();

        if (!$otpToken->can_attempt) {
            $fail(trans('otp.code_max_attempts', ['count' => $otpToken->max_attempts]));

            return;
        }

        if (!$verifyOtpService->codeIsValid(candidate: (string) $value)) {
            $fail(trans('otp.code_not_exists'));
        }
    }
}


