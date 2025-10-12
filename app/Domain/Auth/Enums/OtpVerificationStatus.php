<?php

namespace App\Domain\Auth\Enums;

enum OtpVerificationStatus: string
{
    case Success = 'success';
    case Invalid = 'invalid';
    case Expired = 'expired';
    case Locked = 'locked';
    case Missing = 'missing';
}
