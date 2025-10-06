<?php

namespace App\Models\User;

trait UserMethods
{
    public static function getOtpCodeCacheKeyByPhoneNumber(string $phoneNumber): string
    {
        return "otp_$phoneNumber";
    }

    public function isProfileCompleted(): bool
    {
        return $this->national_id && $this->name && $this->password;
    }
}
