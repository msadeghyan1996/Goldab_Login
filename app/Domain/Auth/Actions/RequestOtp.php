<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\AuthRequestDecision;
use App\Domain\Auth\DTO\OtpContext;
use App\Domain\Auth\Enums\AttemptMethod;
use App\Domain\Auth\Enums\AttemptResult;
use App\Domain\Auth\Enums\AuthNextStep;
use App\Domain\Auth\Events\OtpRequested;
use App\Domain\Auth\Services\AttemptLogger;
use App\Domain\Auth\Services\OtpManager;
use App\Domain\Auth\Services\OtpUserResolver;

readonly class RequestOtp
{
    public function __construct(
        private OtpManager     $manager,
        private AttemptLogger  $logger,
        private OtpUserResolver $users,
    ) {}

    public function handle(OtpContext $context): AuthRequestDecision
    {
        $user = $this->users->findExisting($context->mobile);
        $contextWithUser = $context->withUserId($user?->id);

        if ($user !== null && ! empty($user->password)) {
            $this->logger->log(
                AttemptMethod::RequestOtp,
                AttemptResult::Success,
                $contextWithUser,
                ['next' => AuthNextStep::Password],
                $user->id,
            );

            return AuthRequestDecision::password();
        }

        $result = $this->manager->issue($contextWithUser);
        $attemptResult = $result->issued ? AttemptResult::Success : AttemptResult::Locked;

        $this->logger->log(
            AttemptMethod::RequestOtp,
            $attemptResult,
            $contextWithUser,
            array_filter([
                'next' => AuthNextStep::Otp,
                'expires_at' => $result->expiresAt?->toIso8601String(),
                'locked_until' => $result->lockedUntil?->toIso8601String(),
                'status' => $result->issued ? 'issued' : 'locked',
            ]),
            $user?->id,
        );

        OtpRequested::dispatch($contextWithUser, $result);

        return AuthRequestDecision::otp($result);
    }
}
