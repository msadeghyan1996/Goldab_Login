<?php

namespace App\Livewire\Auth;

use App\Services\User\AuthService;
use App\Services\User\OtpSendService;
use App\Services\User\UserRegisterService;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Login to Goldab')]
class AuthPage extends Component
{
    public $currentStep; // 'login' or 'otp' or 'password' or 'completion'
    public $phoneNumber = '';
    public $isLoading = false;
    public $canResend = false;
    public array $digits = ['', '', '', '', '', ''];
    public int $timerCountDown = 60;

    public string $name = '';
    public string $national_id = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $passwordToLogin = '';

    public function mount(): void
    {
        $authenticatedUser = auth()->user();
        if ($authenticatedUser) {
            if ($authenticatedUser->isProfileCompleted()) {
                $this->redirectToPanel();
                return;
            }
            $this->currentStep = 'completion';
            return;
        }
        $this->currentStep = 'login';
    }

    public function updated($propertyName): void
    {
        $this->validateOnly($propertyName);
    }

    public function login(): void
    {
        $this->validateOnly('phoneNumber');
        $this->isLoading = true;
        $theUser = $this->getAuthService()->findUserByPhone($this->phoneNumber);
        if (!$theUser || !$theUser->isProfileCompleted()) {
            $this->currentStep = 'otp';
            $this->sendOtp();
            $this->startTimer();
        } else {
            $this->currentStep = 'password';
        }
        $this->isLoading = false;
    }

    public function verifyOtp(): void
    {
        $this->validateOnly('digits');
        $this->isLoading = true;
        $isValid = $this->getAuthService()->verifyOTP($this->phoneNumber, implode('', $this->digits));
        if ($isValid) {
            session()?->flash('success', 'OTP verified successfully! Now please enter your data.');
            $registerService = new UserRegisterService($this->phoneNumber);
            $registerService->handle();
            // Login the user
            auth()->login($registerService->getUser());
            if (!$registerService->needsCompletion()) {
                $this->redirectToPanel();
            } else {
                $this->goToCompletion();
            }
            $this->isLoading = false;
            return;
        }
        session()?->flash('error', 'Invalid OTP code. Please try again.');
        $this->digits = ['', '', '', '', '', ''];
        $this->isLoading = false;
    }

    public function completeProfile(): void
    {
        $this->validateOnly('name');
        $this->validateOnly('national_id');
        $this->validateOnly('password');
        $this->getAuthService()->completeFields(auth()->user(), [
            'name' => $this->name,
            'national_id' => $this->national_id,
            'password' => $this->password,
        ]);
        $this->redirectToPanel();
    }

    public function loginWithPassword(): void
    {
        $this->validateOnly('passwordToLogin');
        $user = $this->getAuthService()->loginWithPassword($this->phoneNumber, $this->passwordToLogin);
        if ($user) {
            auth()->login($user);
            $this->redirectToPanel();
            return;
        }
        session()?->flash('error', 'Phone number or password is incorrect. Please try again.');
        $this->passwordToLogin = '';
    }

    public function resendOtp(): void
    {
        $this->sendOtp();
    }

    public function goBack(): void
    {
        $this->currentStep = 'login';
    }

    public function render()
    {
        return view('livewire.auth.auth-page');
    }

    protected function getAuthService(): AuthService
    {
        return new AuthService;
    }

    protected function startTimer(): void
    {
        $this->dispatch('startTimer');
    }

    protected function sendOtp(): void
    {
        $service = new OtpSendService($this->phoneNumber, request()->ip());
        $res = $service->send();
        if ($res) {
            $availableIn = $res['availableIn'] ?? 0;
            $this->timerCountDown = $availableIn;
            session()?->flash('error', "You must wait $availableIn seconds to be able to request for a new otp code!");
        } else {
            session()?->flash('success', 'OTP code sent to your phone!');
        }
    }

    protected function redirectToPanel(): void
    {
        $this->redirectRoute('dashboard');
    }

    protected function goToCompletion(): void
    {
        $this->currentStep = 'completion';
    }

    protected function rules(): array
    {
        return [
            'phoneNumber' => 'required|regex:/^09\d{9}$/',
            'digits' => 'required|array|min:6|max:6',
            'digits.*' => 'required|integer',
            'name' => 'required|string',
            'national_id' => 'required|numeric|digits:10',
            'password' => 'required|min:6|confirmed:password_confirmation',
            'passwordToLogin' => 'required|min:6',
        ];
    }

    protected function getMessages(): array
    {
        return [
            'phoneNumber.required' => 'Please enter your phone number.',
            'phoneNumber.regex' => 'Please enter a valid phone number.',
            'digits.required' => 'Please enter the 6-digit verification code.',
            'digits.min' => 'Please enter the 6-digit verification code.',
            'digits.max' => 'Please enter the 6-digit verification code.',
            'digits.*.integer' => 'Please enter a valid verification code.',
            'name.required' => 'Please enter your name.',
            'name.string' => 'Please enter a valid name.',
            'national_id.required' => 'Please enter your national id.',
            'national_id.numeric' => 'Please enter a valid national id.',
            'national_id.digits' => 'Please enter a valid national id.',
            'password.required' => 'Please enter your password.',
            'password.min' => 'Your password must be at least 6 characters long.',
            'password.confirmed' => 'Your passwords does not match.',
        ];
    }
}
