<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\AuthTokenResult;
use App\Domain\Auth\DTO\OtpContext;
use App\Domain\Auth\DTO\OtpVerificationResult;
use App\Domain\Auth\DTO\VerifyOtpResult;
use App\Domain\Auth\Enums\AttemptMethod;
use App\Domain\Auth\Enums\AttemptResult;
use App\Domain\Auth\Enums\OtpVerificationStatus;
use App\Domain\Auth\Enums\TokenAbility;
use App\Domain\Auth\Events\OtpVerified;
use App\Domain\Auth\Services\AttemptLogger;
use App\Domain\Auth\Services\OtpManager;
use App\Domain\Auth\Services\OtpUserResolver;

readonly class VerifyOtp
{
    public function __construct(
        private OtpManager     $manager,
        private AttemptLogger  $logger,
        private OtpUserResolver $users,
    ) {}

    public function handle(OtpContext $context, string $code): VerifyOtpResult
    {
        $user = $this->users->findExisting($context->mobile);
        $contextWithUser = $context->withUserId($user?->id);

        if ($user !== null && ! empty($user->password)) {
            $this->logger->log(
                AttemptMethod::VerifyOtp,
                AttemptResult::Failure,
                $contextWithUser,
                ['reason' => 'otp_not_allowed'],
                $user->id,
            );

            $verification = OtpVerificationResult::missing();

            OtpVerified::dispatch($contextWithUser, $verification, null);

            return new VerifyOtpResult($verification);
        }

        $verification = $this->manager->verify($context->mobile, $code);

        if (! $verification->isSuccessful()) {
            $this->logger->log(
                AttemptMethod::VerifyOtp,
                $this->mapResult($verification->status),
                $contextWithUser,
                [
                    'status' => $verification->status,
                    'attempts' => $verification->attempts,
                    'remaining_attempts' => $verification->remainingAttempts,
                    'locked_until' => $verification->lockedUntil?->toIso8601String(),
                ],
                $user?->id,
            );

            OtpVerified::dispatch($contextWithUser, $verification, null);

            return new VerifyOtpResult($verification);
        }

        $user ??= $this->users->findOrCreate($context->mobile);
        $contextWithUser = $contextWithUser->withUserId($user->id);
        $ability = $user->hasCompletedProfile()
            ? TokenAbility::AccessApi
            : TokenAbility::PendingProfile;
        $token = $user->createToken(
            name: 'mobile-login',
            abilities: [$ability],
        );

        $this->logger->log(
            AttemptMethod::VerifyOtp,
            AttemptResult::Success,
            $contextWithUser,
            [
                'ability' => $ability,
            ],
            $user->id,
        );

        $authToken = new AuthTokenResult($user, $token->plainTextToken, $ability);
        $result = new VerifyOtpResult($verification, $authToken);

        OtpVerified::dispatch($contextWithUser, $verification, $authToken);

        return $result;
    }

    private function mapResult(OtpVerificationStatus $status): AttemptResult
    {
        return match ($status) {
            OtpVerificationStatus::Success => AttemptResult::Success,
            OtpVerificationStatus::Locked => AttemptResult::Locked,
            OtpVerificationStatus::Expired => AttemptResult::Expired,
            default => AttemptResult::Failure,
        };
    }

}
