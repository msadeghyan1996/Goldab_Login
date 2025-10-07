<?php
namespace App\Services\impl;
use App\Models\OtpCode;
use App\Services\OtpServiceInterface;
use Illuminate\Support\Carbon;
class RedisOtpService extends BaseService implements OtpServiceInterface
{
    protected $cacheService;
    protected $notificationService;
    public function __construct(
        RedisCacheService $cacheService,
        NotificationService $notificationService
    ) {
        $this->cacheService = $cacheService;
        $this->notificationService = $notificationService;
    }
    public function generateOtp(string $mobileNumber, string $purpose): array
    {
        try {
            $recentCount = $this->cacheService->countRecentOtpRequests($mobileNumber, 15);
            $rateLimit = env('RATE_LIMIT_OTP', 3);
            if ($recentCount >= $rateLimit) {
                throw new \Exception('Too many OTP requests. Please try again later.');
            }
            $this->cacheService->deleteOtp($mobileNumber, $purpose);
            $code = $this->generateRandomCode();
            $codeHash = $this->hashCode($code);
            $expirySeconds = env('OTP_EXPIRY', 300);
            $this->cacheService->storeOtp($mobileNumber, $codeHash, $purpose, $expirySeconds);
            $this->cacheService->incrementOtpRequestCount($mobileNumber, 15);
            $expiresAt = Carbon::now()->addSeconds($expirySeconds);
            $this->logInfo('OTP Generated (Redis)', [
                'mobile' => $mobileNumber,
                'purpose' => $purpose,
                'code' => $code, 
                'expires_at' => $expiresAt,
            ]);
            $this->sendOtpNotification($mobileNumber, $code);
            return [
                'code' => $code,
                'expires_at' => $expiresAt,
            ];
        } catch (\Exception $e) {
            $this->handleException($e, 'RedisOtpService::generateOtp');
            throw $e;
        }
    }
    public function verifyOtp(string $mobileNumber, string $code, string $purpose): bool
    {
        try {
            $otpData = $this->cacheService->getOtp($mobileNumber, $purpose);
            if (!$otpData) {
                $this->logWarning('OTP not found in Redis', [
                    'mobile' => $mobileNumber,
                    'purpose' => $purpose,
                ]);
                return false;
            }
            $expiresAt = Carbon::parse($otpData['expires_at']);
            if ($expiresAt->isPast()) {
                $this->logWarning('OTP expired', [
                    'mobile' => $mobileNumber,
                    'purpose' => $purpose,
                    'expires_at' => $expiresAt,
                ]);
                $this->cacheService->deleteOtp($mobileNumber, $purpose);
                return false;
            }
            $maxAttempts = env('OTP_MAX_ATTEMPTS', 3);
            if (($otpData['attempts'] ?? 0) >= $maxAttempts) {
                $this->logWarning('OTP max attempts exceeded', [
                    'mobile' => $mobileNumber,
                    'purpose' => $purpose,
                    'attempts' => $otpData['attempts'],
                ]);
                return false;
            }
            if (!$this->verifyHash($code, $otpData['code_hash'])) {
                $attempts = $this->cacheService->incrementAttempts($mobileNumber, $purpose);
                $this->logWarning('OTP verification failed', [
                    'mobile' => $mobileNumber,
                    'purpose' => $purpose,
                    'attempts' => $attempts,
                ]);
                return false;
            }
            $this->cacheService->deleteOtp($mobileNumber, $purpose);
            $this->logInfo('OTP verified successfully (Redis)', [
                'mobile' => $mobileNumber,
                'purpose' => $purpose,
            ]);
            return true;
        } catch (\Exception $e) {
            $this->handleException($e, 'RedisOtpService::verifyOtp');
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
    protected function sendOtpNotification(string $mobileNumber, string $code): void
    {
        try {
            if (env('APP_DEBUG') && !env('NOTIFICATION_FORCE_SEND', false)) {
                $this->logInfo('📱 Debug Mode - Notifications disabled', [
                    'mobile' => $mobileNumber,
                    'code' => $code,
                    'message' => 'Set NOTIFICATION_FORCE_SEND=true to send in debug mode',
                ]);
                return;
            }
            $this->notificationService->sendOtp(
                $mobileNumber,  
                $code,          
                true            
            );
            $this->logInfo('📤 OTP SMS queued (non-blocking)', [
                'mobile' => substr($mobileNumber, 0, 3) . '****' . substr($mobileNumber, -3),
                'async' => true,
            ]);
        } catch (\Exception $e) {
            $this->handleException($e, 'RedisOtpService::sendOtpNotification');
            $this->logError('❌ Notification queueing failed (OTP still generated)', [
                'mobile' => $mobileNumber,
                'error' => $e->getMessage(),
            ]);
        }
    }
}