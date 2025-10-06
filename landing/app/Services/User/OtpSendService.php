<?php

namespace App\Services\User;

use App\Jobs\SendOtpJob;
use App\Models\User\User;
use Illuminate\Support\Facades\RateLimiter;

class OtpSendService
{
    private string $cacheKey;
    private string $code;

    public function __construct(private string $phoneNumber, private string $ipAddress, private int $invalidateSeconds = 120, private int $resendSeconds = 60)
    {
        $this->cacheKey = User::getOtpCodeCacheKeyByPhoneNumber($this->phoneNumber);
    }

    public function send(): ?array
    {
        $rateReached = $this->ensureUserCanRequestForOtp();
        if ($rateReached) {
            return $rateReached;
        }
        $this->generateOtpForPhoneNumber();
        $this->sendSms();
        return null;
    }

    private function ensureUserCanRequestForOtp(): ?array
    {
        $keyByPhone = "otp_request_counter_by_phone:$this->phoneNumber";
        $keyByIp = "otp_request_counter_by_ip:$this->ipAddress";
        if (
            RateLimiter::tooManyAttempts($keyByPhone, ceil($this->resendSeconds / 60)) ||
            RateLimiter::tooManyAttempts($keyByIp, ceil($this->resendSeconds / 60))
        ) {
            return [
                'availableIn' => max(RateLimiter::availableIn($keyByPhone), RateLimiter::availableIn($keyByIp)),
            ];
        }
        RateLimiter::increment($keyByPhone);
        RateLimiter::increment($keyByIp);
        return null;
    }

    private function generateOtpForPhoneNumber(): void
    {
        cache()->forget($this->cacheKey);
        $this->code = app()->isProduction() ? random_int(100000, 999999) : 111111;
        cache()->put($this->cacheKey, $this->code, now()->addSeconds($this->invalidateSeconds));
    }

    private function sendSms(): void
    {
        dispatch(new SendOtpJob($this->phoneNumber, $this->code));
    }
}
