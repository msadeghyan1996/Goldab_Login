<?php

namespace App\Domain\Auth\Enums;

enum AuthNextStep: string
{
    case Password = 'password';
    case Otp = 'otp';
}
