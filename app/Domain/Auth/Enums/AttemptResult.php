<?php

namespace App\Domain\Auth\Enums;

enum AttemptResult: string
{
    case Success = 'success';
    case Failure = 'failure';
    case Locked = 'locked';
    case Expired = 'expired';
    case RateLimited = 'rate_limited';
}
