<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Contracts\OtpStore;
use App\Domain\Auth\DTO\OtpContext;
use App\Domain\Auth\DTO\OtpIssueResult;
use App\Domain\Auth\DTO\OtpVerificationResult;
use App\Domain\Auth\Jobs\IssueOtpJob;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Config\Repository;

class OtpManager
{
    public function __construct(
        private readonly OtpStore $store,
        private readonly Dispatcher $dispatcher,
        private readonly Repository $config,
    ) {}

    public function issue(OtpContext $context): OtpIssueResult
    {
        $existing = $this->store->get($context->mobile);

        if ($existing !== null && $existing->isLocked()) {
            return OtpIssueResult::locked($existing->lockedUntil);
        }

        $expiresAt = $this->now()->addSeconds($this->ttlSeconds());
        $code = $this->generateCode();
        $hash = $this->hash($code);

        $this->store->put($context->mobile, $hash, $expiresAt);

        $this->dispatcher->dispatch(new IssueOtpJob($context, $code));

        return OtpIssueResult::issued($expiresAt);
    }

    public function verify(string $mobile, string $input): OtpVerificationResult
    {
        $stored = $this->store->get($mobile);

        if ($stored === null) {
            return OtpVerificationResult::missing();
        }

        if ($stored->isLocked()) {
            return OtpVerificationResult::locked($stored->lockedUntil, $stored->attempts);
        }

        if ($stored->isExpired($this->now())) {
            $this->store->clear($mobile);

            return OtpVerificationResult::expired();
        }

        if (hash_equals($stored->codeHash, $this->hash($input))) {
            $this->store->clear($mobile);

            return OtpVerificationResult::success();
        }

        $attempts = $this->store->incrementAttempts($mobile);
        $limit = $this->attemptLimit();
        $remaining = max(0, $limit - $attempts);

        if ($attempts >= $limit) {
            $lockedUntil = $this->now()->addSeconds($this->lockSeconds());
            $this->store->lock($mobile, $lockedUntil);

            return OtpVerificationResult::locked($lockedUntil, $attempts);
        }

        return OtpVerificationResult::invalid($attempts, $remaining);
    }

    private function generateCode(): string
    {
        $length = max(4, (int) $this->config->get('otp.length', 6));
        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) $this->config->get('otp.hmac_key'));
    }

    private function ttlSeconds(): int
    {
        return max(30, (int) $this->config->get('otp.ttl_seconds', 300));
    }

    private function attemptLimit(): int
    {
        return max(1, (int) $this->config->get('otp.attempt_limit', 5));
    }

    private function lockSeconds(): int
    {
        return max(30, (int) $this->config->get('otp.lock_seconds', 900));
    }

    private function now(): CarbonImmutable
    {
        return CarbonImmutable::now();
    }
}
