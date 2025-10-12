<?php

namespace App\Domain\Auth\Enums;

enum TokenAbility: string
{
    case PendingProfile = 'pending-profile';
    case AccessApi = 'access-api';
}
