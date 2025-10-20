<?php

use App\Http\Controllers\Api\V1\Otp\OtpController;
use App\Http\Controllers\Api\V1\Verification\VerificationController;
use App\Http\Controllers\Api\V1\Panel\PanelController;
use Illuminate\Support\Facades\Route;

Route::prefix('otp')->group(function () {
    Route::post('send', [OtpController::class, 'send'])->middleware('throttle:otp-send');
    Route::post('verify', [OtpController::class, 'verify']);
});

Route::middleware('auth:api')->group(function () {
    Route::post('verification', [VerificationController::class, 'verification']);

    Route::middleware('user.verified')->group(function () {
        Route::get('panel', [PanelController::class, 'index']);
    });
});
