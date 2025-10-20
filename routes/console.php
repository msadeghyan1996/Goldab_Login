<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\OtpToken;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('otp:cleanup', function () {
    $days = config('otp.cleanup_days', 2);
    $deleted = OtpToken::where('created_at', '<', now()->subDays($days))->delete();
    $this->info("Deleted {$deleted} expired OTP tokens.");
})->purpose("Delete OTP tokens older than a few days");
