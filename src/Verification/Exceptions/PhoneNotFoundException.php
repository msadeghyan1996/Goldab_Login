<?php

namespace Src\Verification\Exceptions;

class PhoneNotFoundException extends \Exception {
    protected $message = "کدی برای این شماره ثبت نشده است.";

}
