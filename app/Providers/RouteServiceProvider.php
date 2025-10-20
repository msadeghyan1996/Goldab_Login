<?php

namespace App\Providers;

use App\Helper\Response\CustomResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        RateLimiter::for('otp-send', function (Request $request) {
            $phone = (string) $request->input('phone');
            $ip = $request->ip();

            return [
                Limit::perHour(config('otp.max_sends_per_hour', 5))->by("otp:send-hour:$ip")
                    ->after(fn ($response) => $response->getStatusCode() === Response::HTTP_CREATED)
                    ->response(fn ($request, $headers) => CustomResponse::rateLimit($headers)),

                Limit::perSecond(config('otp.max_sends_per_ip_in_cooldown', 3), config('otp.resend_cooldown_seconds', 120))->by("otp:send-ip:$ip")
                    ->after(fn ($response) => $response->getStatusCode() === Response::HTTP_CREATED)
                    ->response(fn ($request, $headers) => CustomResponse::rateLimit($headers)),

                Limit::perSecond(1, config('otp.resend_cooldown_seconds', 120))->by("otp:send:$phone")
                    ->after(fn ($response) => $response->getStatusCode() === Response::HTTP_CREATED)
                    ->response(fn ($request, $headers) => CustomResponse::rateLimit($headers)),
            ];
        });
    }
}
