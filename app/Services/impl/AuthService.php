<?php
namespace App\Services\impl;
use App\Models\OtpCode;
use App\Models\User;
use App\Repositories\LoginAttemptRepositoryInterface;
use App\Repositories\UserRepositoryInterface;
use App\Services\OtpServiceInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
class AuthService extends BaseService
{
    protected $userRepository;
    protected $otpService;
    protected $loginAttemptRepository;
    public function __construct(
        UserRepositoryInterface $userRepository,
        OtpServiceInterface $otpService,
        LoginAttemptRepositoryInterface $loginAttemptRepository
    ) {
        $this->userRepository = $userRepository;
        $this->otpService = $otpService;
        $this->loginAttemptRepository = $loginAttemptRepository;
    }
    public function checkMobile(string $mobileNumber): array
    {
        $user = $this->userRepository->findByMobileNumber($mobileNumber);
        if ($user && $user->hasCompletedRegistration()) {
            return [
                'exists' => true,
                'requires_password' => true,
                'requires_registration' => false,
            ];
        }
        return [
            'exists' => $user !== null,
            'requires_password' => false,
            'requires_registration' => true,
        ];
    }
    public function loginWithPassword(string $mobileNumber, string $password, string $ipAddress, ?string $userAgent = null): array
    {
        try {
            if ($this->loginAttemptRepository->isAccountLocked($mobileNumber)) {
                throw new \Exception('Account is temporarily locked due to too many failed attempts.');
            }
            $user = $this->userRepository->findByMobileNumber($mobileNumber);
            if (!$user || !$user->password) {
                $this->loginAttemptRepository->logAttempt($mobileNumber, $ipAddress, false, $userAgent);
                throw new \Exception('Invalid credentials.');
            }
            if (!password_verify($password, $user->password)) {
                $this->loginAttemptRepository->logAttempt($mobileNumber, $ipAddress, false, $userAgent);
                throw new \Exception('Invalid credentials.');
            }
            $this->loginAttemptRepository->logAttempt($mobileNumber, $ipAddress, true, $userAgent);
            $token = $this->generateToken($user);
            $this->logInfo('User logged in with password', [
                'user_id' => $user->id,
                'mobile' => $mobileNumber,
            ]);
            return [
                'success' => true,
                'user' => $user,
                'token' => $token,
            ];
        } catch (\Exception $e) {
            $this->handleException($e, 'AuthService::loginWithPassword');
            throw $e;
        }
    }
    public function initiateRegistration(string $mobileNumber): array
    {
        try {
            $result = $this->otpService->generateOtp($mobileNumber, OtpCode::PURPOSE_REGISTRATION);
            $this->logInfo('Registration initiated', [
                'mobile' => $mobileNumber,
            ]);
            return [
                'success' => true,
                'message' => 'OTP sent successfully',
                'otp_code' => env('APP_DEBUG') ? $result['code'] : null, 
                'expires_at' => $result['expires_at'] ?? ($result['otp']->expires_at ?? null),
            ];
        } catch (\Exception $e) {
            $this->handleException($e, 'AuthService::initiateRegistration');
            throw $e;
        }
    }
    public function completeRegistration(string $mobileNumber, string $otpCode, array $profileData): array
    {
        try {
            if (!$this->otpService->verifyOtp($mobileNumber, $otpCode, OtpCode::PURPOSE_REGISTRATION)) {
                throw new \Exception('Invalid or expired OTP code.');
            }
            $this->validateProfileData($profileData);
            if ($this->userRepository->nationalIdExists($profileData['national_id'])) {
                throw new \Exception('National ID already registered.');
            }
            $user = $this->userRepository->findByMobileNumber($mobileNumber);
            if (!$user) {
                $user = $this->userRepository->createWithMobile($mobileNumber);
            }
            $updateData = [
                'first_name' => $profileData['first_name'],
                'last_name' => $profileData['last_name'],
                'national_id' => $profileData['national_id'],
                'password' => password_hash($profileData['password'], PASSWORD_BCRYPT),
            ];
            $this->userRepository->updateProfile($user->id, $updateData);
            $user->verifyMobile();
            $user->refresh();
            $token = $this->generateToken($user);
            $this->logInfo('Registration completed', [
                'user_id' => $user->id,
                'mobile' => $mobileNumber,
            ]);
            return [
                'success' => true,
                'user' => $user,
                'token' => $token,
            ];
        } catch (\Exception $e) {
            $this->handleException($e, 'AuthService::completeRegistration');
            throw $e;
        }
    }
    protected function validateProfileData(array $data): void
    {
        $required = ['first_name', 'last_name', 'national_id', 'password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Field {$field} is required.");
            }
        }
        if (!preg_match('/^\d{10}$/', $data['national_id'])) {
            throw new \Exception('National ID must be 10 digits.');
        }
        if (strlen($data['password']) < 8) {
            throw new \Exception('Password must be at least 8 characters long.');
        }
    }
    protected function generateToken(User $user): string
    {
        $payload = [
            'iss' => env('APP_URL'),
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + env('JWT_TTL', 3600),
            'mobile' => $user->mobile_number,
        ];
        return JWT::encode($payload, env('JWT_SECRET'), 'HS256');
    }
    public function verifyToken(string $token): object
    {
        try {
            return JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
        } catch (\Exception $e) {
            throw new \Exception('Invalid token.');
        }
    }
    public function getUserFromToken(string $token): ?User
    {
        try {
            $payload = $this->verifyToken($token);
            return $this->userRepository->find($payload->sub);
        } catch (\Exception $e) {
            return null;
        }
    }
}