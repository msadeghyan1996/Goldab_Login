<?php

use App\Http\Controllers\Api\V1\Auth\CompleteProfileController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\PasswordLoginController;
use App\Http\Controllers\Api\V1\Auth\RequestAuthController;
use App\Http\Controllers\Api\V1\Auth\VerifyOtpController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('request', RequestAuthController::class)
            ->name('api.v1.auth.request')
            ->middleware('throttle:otp-request');

        Route::post('verify-otp', VerifyOtpController::class)
            ->name('api.v1.auth.verify-otp')
            ->middleware('throttle:otp-verify');

        Route::post('login', PasswordLoginController::class)
            ->name('api.v1.auth.login');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('complete-profile', CompleteProfileController::class)
                ->name('api.v1.auth.complete-profile')
                ->middleware('ability:pending-profile');

            Route::post('logout', LogoutController::class)
                ->name('api.v1.auth.logout');
        });
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('me', MeController::class)
            ->name('api.v1.me');
    });
});
