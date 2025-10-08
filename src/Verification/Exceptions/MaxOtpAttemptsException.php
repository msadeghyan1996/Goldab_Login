<?php

namespace Src\Verification\Exceptions;

class MaxOtpAttemptsException extends \Exception {
    protected $message = "تعداد تلاش شما برای وارد کردن کد بیش از حد مجاز است.";
}
