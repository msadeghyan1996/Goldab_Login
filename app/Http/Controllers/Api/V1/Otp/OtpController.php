<?php

namespace App\Http\Controllers\Api\V1\Otp;

use App\Helper\Log\Log;
use App\Helper\Response\CustomResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Otp\SendRequest;
use App\Http\Requests\Api\V1\Otp\VerifyRequest;
use App\Models\Enum\OtpPurpose;
use App\Services\Otp\SendOtpService;
use App\Services\Otp\VerifyOtpService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as ResCode;
use Throwable;

class OtpController extends Controller
{
    /**
     * Sends an OTP to the provided phone number.
     *
     * Determines purpose as login for existing users, register otherwise.
     * Returns SMS dispatch acknowledgement and expiration timestamp.
     *
     * @param SendRequest $request
     * @param SendOtpService $sendOtpService
     * @param UserService $userService
     *
     * @return JsonResponse
     */
    public function send(SendRequest $request, SendOtpService $sendOtpService, UserService $userService): JsonResponse
    {
        try {
            DB::beginTransaction();

            $otpToken = $sendOtpService->issue(
                phone: $request->phone,
                purpose: $userService->setPhone($request->phone)->isVerificationCompleted() ? OtpPurpose::LOGIN : OtpPurpose::REGISTER,
                request: $request
            );

            $seconds = (int) max(0, now()->diffInSeconds($otpToken->expires_at));

            DB::commit();

            return Response::json([
                'message' => trans('otp.sent', ['seconds' => $seconds]),
                'expires_in' => $seconds,
                'expires_at' => $otpToken->expires_at->timestamp,
            ], ResCode::HTTP_CREATED);
        } catch (Throwable $throwable) {
            DB::rollBack();
            Log::withException($throwable)->error(Log::sharedContext());

            return CustomResponse::internalError();
        }
    }

    /**
     * Verify an OTP for the given phone number, create/find the user, and return a JWT.
     *
     * Starts a DB transaction, fetches the latest active OTP, marks it consumed,
     * ensures a `User` exists via `UserService`, authenticates via the `api` guard,
     * and returns a JSON payload containing the bearer token, its TTL (seconds),
     * and the next step (`register` or `panel`).
     *
     * @param VerifyRequest $request Validated request with the phone number.
     * @param VerifyOtpService $verifyOtpService Service to retrieve/consume OTP tokens.
     * @param UserService $userService Service to create or fetch the user by phone.
     *
     * @return JsonResponse JSON with `token`, `next_step`, and a localized `message`.
     */
    public function verify(VerifyRequest $request, VerifyOtpService $verifyOtpService, UserService $userService): JsonResponse
    {
        try {
            DB::beginTransaction();
            $otpToken = $verifyOtpService->setPhone($request->phone)->consume()->getOtpToken();

            $token = $userService->setPhone(phone: $request->phone)->getToken();

            DB::commit();

            return response()->json([
                'next_step' => $otpToken->purpose == OtpPurpose::REGISTER ? 'verification' : 'panel',
                'token' => $token,
                'message' => trans('otp.loggedin')
            ]);
        } catch (Throwable $throwable) {
            DB::rollBack();
            Log::withException($throwable)->error(Log::sharedContext());

            return CustomResponse::internalError();
        }
    }
}
