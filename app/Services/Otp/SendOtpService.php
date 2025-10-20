<?php

namespace App\Services\Otp;

use App\Models\Enum\OtpPurpose;
use App\Models\OtpToken;
use App\Services\SmsService;
use Exception;
use Illuminate\Http\Request;

class SendOtpService
{
    public function __construct(private readonly SmsService $sms)
    {
    }

    /**
     * Issue and dispatch an OTP for the given phone and purpose.
     *
     * Persists a salted + peppered hash with metadata, then sends the plain code via SMS.
     *
     * @param string $phone E.164 phone number including leading '+'.
     * @param OtpPurpose $purpose
     * @param Request $request
     *
     * @return OtpToken The created OTP token model.
     *
     * @throws Exception
     */
    public function issue(string $phone, OtpPurpose $purpose, Request $request): ?OtpToken
    {
        $code = $this->generateCode();

        $otp = OtpToken::create([
            'phone' => $phone,
            'code_hash' => $code,
            'purpose' => $purpose,
            'attempts_count' => 0,
            'max_attempts' => config('otp.max_attempts', 5),
            'expires_at' => now()->addMinutes(config('otp.ttl_minutes', 2)),
            'request_ip' => $request->ip(),
            'device_id' => $request->header('X-Device-Id'),
        ]);

        OtpCache::set($otp);

        $this->sms->send($phone, $purpose, $code);

        return $otp;
    }

    /**
     * Generate a cryptographically secure fixed-length numeric OTP code.
     *
     * Length is read from `otp.length` config (default 6). Uses random_int for
     * uniform distribution and left-pads with zeros to enforce fixed length.
     *
     * @return string Numeric OTP as a zero-padded string of configured length.
     * @throws Exception If a secure random number cannot be generated.
     */
    private function generateCode(): string
    {
        $length = config('otp.length', 6);
        return str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
    }
}
