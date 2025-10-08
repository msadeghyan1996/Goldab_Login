<?php

namespace Src;
final class Helper {
    public function generateVerifyCode() : string {
        return rand(100000,999999);
    }
}
