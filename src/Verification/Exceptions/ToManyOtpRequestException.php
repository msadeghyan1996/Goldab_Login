<?php

namespace Src\Verification\Exceptions;

class ToManyOtpRequestException extends \Exception{
    protected $message = 'شما بیش از حد مجاز درخواست کد کرده‌اید.';
}
