<?php

use App\Http\Controllers\api\v1\User\UserController;
use Illuminate\Support\Facades\Route;

Route::controller(UserController::class)->group(function () {

    Route::prefix('user')
        ->middleware('auth:sanctum')
        ->group(function () {

        Route::post('/complete-profile', [UserController::class, 'completeProfile']);

    });

});
