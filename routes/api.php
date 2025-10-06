<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;


Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/otp-verify', [AuthController::class, 'otpVerify'])->middleware('throttle:5,1');
