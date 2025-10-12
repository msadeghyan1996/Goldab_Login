<?php

namespace App\Domain\Auth\Enums;

enum AttemptMethod: string
{
    case RequestOtp = 'request_otp';
    case VerifyOtp = 'verify_otp';
    case PasswordLogin = 'password_login';
    case CompleteProfile = 'complete_profile';
}
