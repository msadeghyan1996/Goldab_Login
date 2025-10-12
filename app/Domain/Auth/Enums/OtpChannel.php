<?php

namespace App\Domain\Auth\Enums;

enum OtpChannel: string
{
    case Web = 'web';
    case Api = 'api';
}
