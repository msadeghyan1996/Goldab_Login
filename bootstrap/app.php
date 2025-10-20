<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AcceptJsonMiddleware;
use App\Http\Middleware\EnsureUserVerified;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        then: function () {
            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/api/v1.php'));
        }
    )
    ->withMiddleware(callback: function (Middleware $middleware): void {
        $middleware->append(middleware: [
            AcceptJsonMiddleware::class,
        ]);
        $middleware->alias([
            'user.verified' => EnsureUserVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('otp:cleanup')->dailyAt('02:30');
    })
    ->create();
