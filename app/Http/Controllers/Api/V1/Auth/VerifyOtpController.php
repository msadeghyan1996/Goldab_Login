<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Auth\Actions\VerifyOtp;
use App\Domain\Auth\DTO\OtpContext;
use App\Domain\Auth\DTO\OtpVerificationResult;
use App\Domain\Auth\Enums\OtpChannel;
use App\Domain\Auth\Enums\OtpVerificationStatus;
use App\Domain\Auth\Enums\TokenAbility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyOtpRequest;
use Illuminate\Http\JsonResponse;

class VerifyOtpController extends Controller
{
    public function __construct(private readonly VerifyOtp $action) {}

    public function __invoke(VerifyOtpRequest $request): JsonResponse
    {
        $context = new OtpContext(
            mobile: (string) $request->input('mobile'),
            ip: (string) $request->ip(),
            userAgent: (string) $request->userAgent(),
            channel: OtpChannel::Api,
        );

        $result = $this->action->handle($context, (string) $request->input('otp'));
        $verification = $result->verification;

        if ($verification->status === OtpVerificationStatus::Success && $result->token !== null) {
            $ability = $result->token->ability;
            $status = $ability === TokenAbility::AccessApi ? 'ok' : 'pending_profile';

            return response()->json([
                'token' => $result->token->plainTextToken,
                'status' => $status,
            ]);
        }

        return $this->failureResponse($verification);
    }

    private function failureResponse(OtpVerificationResult $verification): JsonResponse
    {
        $status = $verification->status;
        $payload = array_filter([
            'status' => match ($status) {
                OtpVerificationStatus::Locked => 'locked',
                OtpVerificationStatus::Expired => 'expired',
                default => 'invalid',
            },
            'remaining_attempts' => $verification->remainingAttempts,
            'locked_until' => $verification->lockedUntil?->toIso8601String(),
        ]);

        $code = $status === OtpVerificationStatus::Locked ? 423 : 422;

        return response()->json($payload, $code);
    }
}
