<?php
namespace App\Services\impl;
use App\Models\OtpCode;
use App\Repositories\OtpRepositoryInterface;
use App\Services\OtpServiceInterface;
class OtpService extends BaseService implements OtpServiceInterface
{
    protected $otpRepository;
    public function __construct(OtpRepositoryInterface $otpRepository)
    {
        $this->otpRepository = $otpRepository;
    }
    public function generateOtp(string $mobileNumber, string $purpose): array
    {
        try {
            $recentCount = $this->otpRepository->countRecentOtps($mobileNumber, 15);
            $rateLimit = env('RATE_LIMIT_OTP', 3);
            if ($recentCount >= $rateLimit) {
                throw new \Exception('Too many OTP requests. Please try again later.');
            }
            $this->otpRepository->invalidatePreviousOtps($mobileNumber, $purpose);
            $code = $this->generateRandomCode();
            $codeHash = $this->hashCode($code);
            $expirySeconds = env('OTP_EXPIRY', 300);
            $otp = $this->otpRepository->createOtp($mobileNumber, $codeHash, $purpose, $expirySeconds);
            $this->logInfo('OTP Generated', [
                'mobile' => $mobileNumber,
                'purpose' => $purpose,
                'code' => $code, 
                'expires_at' => $otp->expires_at,
            ]);
            return [
                'code' => $code,
                'otp' => $otp,
            ];
        } catch (\Exception $e) {
            $this->handleException($e, 'OtpService::generateOtp');
            throw $e;
        }
    }
    public function verifyOtp(string $mobileNumber, string $code, string $purpose): bool
    {
        try {
            $otp = $this->otpRepository->findValidOtp($mobileNumber, $purpose);
            if (!$otp) {
                $this->logWarning('OTP not found or expired', [
                    'mobile' => $mobileNumber,
                    'purpose' => $purpose,
                ]);
                return false;
            }
            if ($otp->hasExceededAttempts()) {
                $this->logWarning('OTP max attempts exceeded', [
                    'mobile' => $mobileNumber,
                    'purpose' => $purpose,
                    'attempts' => $otp->attempts,
                ]);
                return false;
            }
            if (!$this->verifyHash($code, $otp->code_hash)) {
                $otp->incrementAttempts();
                $this->logWarning('OTP verification failed', [
                    'mobile' => $mobileNumber,
                    'purpose' => $purpose,
                    'attempts' => $otp->attempts,
                ]);
                return false;
            }
            $otp->markAsUsed();
            $this->logInfo('OTP verified successfully', [
                'mobile' => $mobileNumber,
                'purpose' => $purpose,
            ]);
            return true;
        } catch (\Exception $e) {
            $this->handleException($e, 'OtpService::verifyOtp');
            throw $e;
        }
    }
    protected function generateRandomCode(): string
    {
        $length = env('OTP_LENGTH', 6);
        $min = pow(10, $length - 1);
        $max = pow(10, $length) - 1;
        return str_pad((string)random_int($min, $max), $length, '0', STR_PAD_LEFT);
    }
    protected function hashCode(string $code): string
    {
        return hash('sha256', $code . env('APP_KEY'));
    }
    protected function verifyHash(string $code, string $hash): bool
    {
        return hash_equals($hash, $this->hashCode($code));
    }
    public function cleanupExpiredOtps(): int
    {
        return $this->otpRepository->cleanupExpiredOtps();
    }
}