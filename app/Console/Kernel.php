<?php
namespace App\Console;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
class Kernel extends ConsoleKernel
{
    protected $commands = [
    ];
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            $otpService = app(\App\Services\OtpService::class);
            $deleted = $otpService->cleanupExpiredOtps();
            \Log::info("Cleaned up {$deleted} expired OTPs");
        })->daily();
    }
}