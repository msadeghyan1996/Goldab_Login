<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\AuthTokenResult;
use App\Domain\Auth\DTO\OtpContext;
use App\Domain\Auth\DTO\PasswordLoginResult;
use App\Domain\Auth\Enums\AttemptMethod;
use App\Domain\Auth\Enums\AttemptResult;
use App\Domain\Auth\Enums\TokenAbility;
use App\Domain\Auth\Events\LoginFailed;
use App\Domain\Auth\Services\AttemptLogger;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

readonly class PasswordLogin
{
    public function __construct(private AttemptLogger $logger) {}

    public function handle(OtpContext $context, string $password): PasswordLoginResult
    {
        $user = User::query()->where('mobile', $context->mobile)->first();

        $reason = match (true) {
            $user === null => 'user_not_found',
            empty($user->password) => 'password_not_set',
            ! Hash::check($password, $user->password) => 'invalid_credentials',
            default => null,
        };

        if ($reason !== null) {
            $this->logger->log(
                AttemptMethod::PasswordLogin,
                AttemptResult::Failure,
                $context,
                ['reason' => $reason],
                $user?->id,
            );

            LoginFailed::dispatch($context, $reason);

            return PasswordLoginResult::failure($reason);
        }

        $ability = $user->hasCompletedProfile()
            ? TokenAbility::AccessApi
            : TokenAbility::PendingProfile;

        $token = $user->createToken('password-login', [$ability]);

        $authenticatedContext = new OtpContext(
            mobile: $context->mobile,
            ip: $context->ip,
            userAgent: $context->userAgent,
            channel: $context->channel,
            userId: $user->id,
        );

        $this->logger->log(
            AttemptMethod::PasswordLogin,
            AttemptResult::Success,
            $authenticatedContext,
            ['ability' => $ability],
            $user->id,
        );

        return PasswordLoginResult::success(
            new AuthTokenResult($user, $token->plainTextToken, $ability),
        );
    }
}
