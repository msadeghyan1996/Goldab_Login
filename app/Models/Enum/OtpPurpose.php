<?php

namespace App\Models\Enum;

enum OtpPurpose: int
{
    case REGISTER = 1;
    case LOGIN = 2;

    public function label(): string
    {
        return match ($this) {
            self::REGISTER => __('otp.purpose.register'),
            self::LOGIN => __('otp.purpose.login'),
        };
    }
}
