<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Auth\Actions\RequestOtp;
use App\Domain\Auth\DTO\OtpContext;
use App\Domain\Auth\Enums\AuthNextStep;
use App\Domain\Auth\Enums\OtpChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RequestAuthRequest;
use Illuminate\Http\JsonResponse;

class RequestAuthController extends Controller
{
    public function __construct(private readonly RequestOtp $action) {}

    public function __invoke(RequestAuthRequest $request): JsonResponse
    {
        $context = new OtpContext(
            mobile: (string) $request->input('mobile'),
            ip: (string) $request->ip(),
            userAgent: (string) $request->userAgent(),
            channel: OtpChannel::Api,
        );

        $decision = $this->action->handle($context);

        if ($decision->next === AuthNextStep::Password) {
            return response()->json([
                'next' => AuthNextStep::Password,
            ]);
        }

        $otpResult = $decision->otpResult;

        if ($decision->isLocked()) {
            return response()->json([
                'next' => AuthNextStep::Otp,
                'status' => 'locked',
                'locked_until' => $otpResult?->lockedUntil?->toIso8601String(),
            ], 423);
        }

        if ($otpResult === null) {
            return response()->json([
                'next' => AuthNextStep::Otp,
            ]);
        }

        return response()->json([
            'next' => AuthNextStep::Otp,
            'expires_at' => $otpResult->expiresAt?->toIso8601String(),
        ]);
    }
}
