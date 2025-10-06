<?php

use App\Http\Controllers\Api\AuthController;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware('throttle:5,1')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('otp-verify', [AuthController::class, 'otpVerify']);
    Route::post('update-info', [AuthController::class, 'updateInfo']);
});
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('home', function (Request $request) {
        /**
         * @var User $user
         */
        $user = $request->user();
        if ( !$user ) {
            throw new AuthorizationException();
        }

        return response()->json([...$user->toArray(), 'statusLabel' => $user->status->label() ?? '']);
    });
});
