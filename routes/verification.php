<?php

use App\Http\Controllers\api\v1\Verification\VerificationController;
use Illuminate\Support\Facades\Route;

Route::controller(VerificationController::class)->group(function () {

    Route::prefix('verification')->middleware('throttle:10,1')->group(function () {

        Route::post('create', [VerificationController::class, 'create']);

        Route::post('verify', [VerificationController::class, 'verify']);

    });

});
