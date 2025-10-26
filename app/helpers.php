<?php

if(!function_exists('otpService'))
{
    function otpService()
    {
        return app(App\Services\OtpService::class);
    }
}
