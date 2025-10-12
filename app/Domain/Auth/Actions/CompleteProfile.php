<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\AuthTokenResult;
use App\Domain\Auth\DTO\OtpContext;
use App\Domain\Auth\Enums\AttemptMethod;
use App\Domain\Auth\Enums\AttemptResult;
use App\Domain\Auth\Enums\TokenAbility;
use App\Domain\Auth\Events\ProfileCompleted;
use App\Domain\Auth\Services\AttemptLogger;
use App\Domain\Auth\Services\NationalIdValidator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

readonly class CompleteProfile
{
    public function __construct(
        private NationalIdValidator $validator,
        private AttemptLogger       $logger,
    ) {}

    public function handle(
        User $user,
        OtpContext $context,
        string $firstName,
        string $lastName,
        string $nationalId,
        string $password,
    ): AuthTokenResult {
        if (! $this->validator->isValid($nationalId)) {
            throw new InvalidArgumentException('The provided national ID is invalid.');
        }

        $payload = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'national_id' => $nationalId,
            'password' => Hash::make($password),
        ];

        $user->forceFill($payload)->save();

        $contextToken = $user->currentAccessToken();

        if ($contextToken !== null) {
            $contextToken->delete();
        }

        $token = $user->createToken('mobile-login', [TokenAbility::AccessApi]);

        $enrichedContext = new OtpContext(
            mobile: $context->mobile,
            ip: $context->ip,
            userAgent: $context->userAgent,
            channel: $context->channel,
            userId: $user->id,
        );

        $this->logger->log(
            AttemptMethod::CompleteProfile,
            AttemptResult::Success,
            $enrichedContext,
            ['national_id' => $nationalId],
            $user->id,
        );

        ProfileCompleted::dispatch($user);

        return new AuthTokenResult($user, $token->plainTextToken, TokenAbility::AccessApi);
    }
}
