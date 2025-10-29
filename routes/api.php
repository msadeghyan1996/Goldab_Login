<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('/auth')->controller(AuthController::class)->group(function () {
    Route::post('/lookup', 'lookup')->middleware('throttle:5');

    Route::post('/login', 'loginWithPassword');

    Route::prefix('/register')->group(function () {
        Route::prefix('/otp')->group(function () {
            Route::post('/request', 'requestRegistrationOtp');
            Route::post('/verify', 'verifyRegistrationOtp');
        });
        Route::post('/', 'completeRegistration');
    });

    Route::middleware('auth')->group(function () {
        Route::get('/me', 'me');
        Route::post('/logout', 'logout');
    });
});


Route::middleware('auth:sanctum')->group(function () {
    // Panel related routes can be implemented here :)
});
