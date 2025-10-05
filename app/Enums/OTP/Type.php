<?php

namespace App\Enums\OTP;


use App\Traits\TranslatableEnum;

enum Type: string {
    use TranslatableEnum;

    case Login = 'login';
    case Register = 'register';
    case RESET_PASSWORD = 'reset_password';


    public function label () : string {
        return match ($this) {
            self::Login => 'ورود',
            self::Register => 'ثبت نام',
            self::RESET_PASSWORD => 'بازیابی رمز عبور',
        };
    }
}
