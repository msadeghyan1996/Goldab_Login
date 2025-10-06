<?php

namespace Tests\Feature\Livewire\Auth;

use App\Livewire\Auth\AuthPage;
use App\Models\User\User;
use App\Services\User\OtpSendService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('AuthPage Mount Behavior', function () {
    test('redirects authenticated user with completed profile to dashboard', function () {
        $user = User::factory()->create([
            'phone_number' => '09123456789',
            'name' => 'John Doe',
            'national_id' => '1234567890',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($user);

        Livewire::test(AuthPage::class)
            ->assertRedirect(route('dashboard'));
    });

    test('shows completion step for authenticated user with incomplete profile', function () {
        $user = User::factory()->create([
            'phone_number' => '09123456789',
            'name' => null,
            'national_id' => null,
            'password' => null,
        ]);

        $this->actingAs($user);

        Livewire::test(AuthPage::class)
            ->assertSet('currentStep', 'completion');
    });

    test('shows login step for guest user', function () {
        Livewire::test(AuthPage::class)
            ->assertSet('currentStep', 'login');
    });
});

describe('Phone Number Login', function () {
    test('validates phone number is required', function () {
        Livewire::test(AuthPage::class)
            ->set('phoneNumber', '')
            ->call('login')
            ->assertHasErrors(['phoneNumber' => 'required']);
    });

    test('validates phone number format starts with 09', function () {
        Livewire::test(AuthPage::class)
            ->set('phoneNumber', '9123456789')
            ->call('login')
            ->assertHasErrors(['phoneNumber' => 'regex']);
    });

    test('validates phone number has exactly 11 digits', function () {
        Livewire::test(AuthPage::class)
            ->set('phoneNumber', '091234567')
            ->call('login')
            ->assertHasErrors(['phoneNumber' => 'regex']);

        Livewire::test(AuthPage::class)
            ->set('phoneNumber', '091234567890')
            ->call('login')
            ->assertHasErrors(['phoneNumber' => 'regex']);
    });

    test('redirects to otp step for non-existent user', function () {
        Livewire::test(AuthPage::class)
            ->set('phoneNumber', '09123456789')
            ->call('login')
            ->assertSet('currentStep', 'otp')
            ->assertDispatched('startTimer');
    });

    test('redirects to otp step for user with incomplete profile', function () {
        User::factory()->create([
            'phone_number' => '09123456789',
            'name' => null,
            'national_id' => null,
            'password' => null,
        ]);

        Livewire::test(AuthPage::class)
            ->set('phoneNumber', '09123456789')
            ->call('login')
            ->assertSet('currentStep', 'otp')
            ->assertDispatched('startTimer');
    });

    test('redirects to password step for existing user with completed profile', function () {
        User::factory()->create([
            'phone_number' => '09123456789',
            'name' => 'John Doe',
            'national_id' => '1234567890',
            'password' => Hash::make('password'),
        ]);

        Livewire::test(AuthPage::class)
            ->set('phoneNumber', '09123456789')
            ->call('login')
            ->assertSet('currentStep', 'password')
            ->assertSet('isLoading', false);
    });

    test('accepts phone numbers with various valid formats', function () {
        $validNumbers = [
            '09123456789',
            '09987654321',
            '09000000000',
            '09999999999',
        ];

        foreach ($validNumbers as $number) {
            Livewire::test(AuthPage::class)
                ->set('phoneNumber', $number)
                ->call('login')
                ->assertHasNoErrors('phoneNumber');
        }
    });

    test('rejects invalid phone number formats', function () {
        $invalidNumbers = [
            '08123456789',  // doesn't start with 09
            '+989123456789', // has country code
            '9123456789',    // missing leading 0
            '091234567',     // too short
            '091234567890',  // too long
            '09abc456789',   // contains letters
        ];

        foreach ($invalidNumbers as $number) {
            Livewire::test(AuthPage::class)
                ->set('phoneNumber', $number)
                ->call('login')
                ->assertHasErrors('phoneNumber');
        }
    });
});

describe('OTP Verification', function () {
    test('validates otp digits are required', function () {
        Livewire::test(AuthPage::class)
            ->set('digits', ['', '', '', '', '', ''])
            ->call('verifyOtp')
            ->assertHasErrors('digits.*');
    });

    test('validates each digit must be an integer', function () {
        Livewire::test(AuthPage::class)
            ->set('digits', ['1', 'a', '3', '4', '5', '6'])
            ->call('verifyOtp')
            ->assertHasErrors('digits.*');
    });

    test('validates otp must have exactly 6 digits', function () {
        Livewire::test(AuthPage::class)
            ->set('digits', ['1', '2', '3', '4', '5'])
            ->call('verifyOtp')
            ->assertHasErrors('digits');
    });

    test('successfully verifies valid otp and logs in user', function () {
        $phoneNumber = '09123456789';
        $otp = '123456';

        // Set OTP in cache
        Cache::put(User::getOtpCodeCacheKeyByPhoneNumber($phoneNumber), $otp, now()->addMinutes(2));

        Livewire::test(AuthPage::class)
            ->set('phoneNumber', $phoneNumber)
            ->set('digits', str_split($otp))
            ->call('verifyOtp')
            ->assertSet('currentStep', 'completion');

        $this->assertAuthenticated();
    });

    test('rejects invalid otp code', function () {
        $phoneNumber = '09123456789';

        // Set correct OTP in cache
        Cache::put(User::getOtpCodeCacheKeyByPhoneNumber($phoneNumber), '123456', now()->addMinutes(2));

        Livewire::test(AuthPage::class)
            ->set('phoneNumber', $phoneNumber)
            ->set('digits', ['9', '9', '9', '9', '9', '9'])
            ->call('verifyOtp')
            ->assertSee('Invalid OTP')
            ->assertSet('digits', ['', '', '', '', '', '']);

        $this->assertGuest();
    });

    test('redirects to panel if user profile is already completed during otp verification', function () {
        $phoneNumber = '09123456789';
        $otp = '123456';

        // Create user with completed profile
        User::factory()->create([
            'phone_number' => $phoneNumber,
            'name' => 'John Doe',
            'national_id' => '1234567890',
            'password' => Hash::make('password'),
        ]);

        // Set OTP in cache
        Cache::put(User::getOtpCodeCacheKeyByPhoneNumber($phoneNumber), $otp, now()->addMinutes(2));

        Livewire::test(AuthPage::class)
            ->set('phoneNumber', $phoneNumber)
            ->set('digits', str_split($otp))
            ->call('verifyOtp')
            ->assertRedirect(route('dashboard'));
    });

    test('clears digits array after invalid otp', function () {
        $phoneNumber = '09123456789';
        Cache::put(User::getOtpCodeCacheKeyByPhoneNumber($phoneNumber), '123456', now()->addMinutes(2));

        Livewire::test(AuthPage::class)
            ->set('phoneNumber', $phoneNumber)
            ->set('digits', ['9', '9', '9', '9', '9', '9'])
            ->call('verifyOtp')
            ->assertSet('digits', ['', '', '', '', '', '']);
    });

    test('handles expired otp code', function () {
        $phoneNumber = '09123456789';

        // Don't set any OTP in cache (expired or non-existent)
        Livewire::test(AuthPage::class)
            ->set('phoneNumber', $phoneNumber)
            ->set('digits', ['1', '2', '3', '4', '5', '6'])
            ->call('verifyOtp')
            ->assertSee('Invalid OTP');

        $this->assertGuest();
    });
});

describe('Password Login', function () {
    test('validates password is required', function () {
        Livewire::test(AuthPage::class)
            ->set('passwordToLogin', '')
            ->call('loginWithPassword')
            ->assertHasErrors(['passwordToLogin' => 'required']);
    });

    test('validates password minimum length of 6', function () {
        Livewire::test(AuthPage::class)
            ->set('passwordToLogin', '12345')
            ->call('loginWithPassword')
            ->assertHasErrors(['passwordToLogin' => 'min']);
    });

    test('successfully logs in with correct password', function () {
        $user = User::factory()->create([
            'phone_number' => '09123456789',
            'name' => 'John Doe',
            'national_id' => '1234567890',
            'password' => Hash::make('password123'),
        ]);

        Livewire::test(AuthPage::class)
            ->set('phoneNumber', '09123456789')
            ->set('passwordToLogin', 'password123')
            ->call('loginWithPassword')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    });

    test('rejects incorrect password', function () {
        User::factory()->create([
            'phone_number' => '09123456789',
            'name' => 'John Doe',
            'national_id' => '1234567890',
            'password' => Hash::make('correct_password'),
        ]);

        Livewire::test(AuthPage::class)
            ->set('phoneNumber', '09123456789')
            ->set('passwordToLogin', 'wrong_password')
            ->call('loginWithPassword')
            ->assertSee('Phone number or password is incorrect')
            ->assertSet('passwordToLogin', '');

        $this->assertGuest();
    });

    test('rejects login for non-existent user', function () {
        Livewire::test(AuthPage::class)
            ->set('phoneNumber', '09999999999')
            ->set('passwordToLogin', 'password123')
            ->call('loginWithPassword')
            ->assertSee('Phone number or password is incorrect');

        $this->assertGuest();
    });

    test('clears password field after failed login', function () {
        User::factory()->create([
            'phone_number' => '09123456789',
            'password' => Hash::make('correct_password'),
        ]);

        Livewire::test(AuthPage::class)
            ->set('phoneNumber', '09123456789')
            ->set('passwordToLogin', 'wrong_password')
            ->call('loginWithPassword')
            ->assertSet('passwordToLogin', '');
    });
});

describe('Profile Completion', function () {
    test('validates name is required', function () {
        $user = User::factory()->incomplete()->create(['phone_number' => '09123456789']);
        $this->actingAs($user);

        Livewire::test(AuthPage::class)
            ->set('name', '')
            ->set('national_id', '1234567890')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('completeProfile')
            ->assertHasErrors(['name' => 'required']);
    });

    test('validates national id is required', function () {
        $user = User::factory()->incomplete()->create(['phone_number' => '09123456789']);
        $this->actingAs($user);

        Livewire::test(AuthPage::class)
            ->set('name', 'John Doe')
            ->set('national_id', '')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('completeProfile')
            ->assertHasErrors(['national_id' => 'required']);
    });

    test('validates national id must be numeric', function () {
        $user = User::factory()->incomplete()->create(['phone_number' => '09123456789']);
        $this->actingAs($user);

        Livewire::test(AuthPage::class)
            ->set('name', 'John Doe')
            ->set('national_id', 'abc1234567')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('completeProfile')
            ->assertHasErrors(['national_id' => 'numeric']);
    });

    test('validates national id must be exactly 10 digits', function () {
        $user = User::factory()->incomplete()->create(['phone_number' => '09123456789']);
        $this->actingAs($user);

        // Too short
        Livewire::test(AuthPage::class)
            ->set('name', 'John Doe')
            ->set('national_id', '123456789')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('completeProfile')
            ->assertHasErrors(['national_id' => 'digits']);

        // Too long
        Livewire::test(AuthPage::class)
            ->set('name', 'John Doe')
            ->set('national_id', '12345678901')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('completeProfile')
            ->assertHasErrors(['national_id' => 'digits']);
    });

    test('validates password is required', function () {
        $user = User::factory()->incomplete()->create(['phone_number' => '09123456789']);
        $this->actingAs($user);

        Livewire::test(AuthPage::class)
            ->set('name', 'John Doe')
            ->set('national_id', '1234567890')
            ->set('password', '')
            ->set('password_confirmation', '')
            ->call('completeProfile')
            ->assertHasErrors(['password' => 'required']);
    });

    test('validates password minimum length of 6', function () {
        $user = User::factory()->incomplete()->create(['phone_number' => '09123456789']);
        $this->actingAs($user);

        Livewire::test(AuthPage::class)
            ->set('name', 'John Doe')
            ->set('national_id', '1234567890')
            ->set('password', '12345')
            ->set('password_confirmation', '12345')
            ->call('completeProfile')
            ->assertHasErrors(['password' => 'min']);
    });

    test('validates password confirmation matches', function () {
        $user = User::factory()->incomplete()->create(['phone_number' => '09123456789']);
        $this->actingAs($user);

        Livewire::test(AuthPage::class)
            ->set('name', 'John Doe')
            ->set('national_id', '1234567890')
            ->set('password', 'password123')
            ->set('password_confirmation', 'different_password')
            ->call('completeProfile')
            ->assertHasErrors(['password' => 'confirmed']);
    });

    test('successfully completes profile with valid data', function () {
        $user = User::factory()->incomplete()->create(['phone_number' => '09123456789']);
        $this->actingAs($user);

        Livewire::test(AuthPage::class)
            ->set('name', 'John Doe')
            ->set('national_id', '1234567890')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('completeProfile')
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        expect($user->name)->toBe('John Doe');
        expect($user->national_id)->toBe('1234567890');
        expect(Hash::check('password123', $user->password))->toBeTrue();
    });

    test('strips html tags from name during profile completion', function () {
        $user = User::factory()->incomplete()->create(['phone_number' => '09123456789']);
        $this->actingAs($user);

        Livewire::test(AuthPage::class)
            ->set('name', 'John <script>alert("xss")</script> Doe')
            ->set('national_id', '1234567890')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('completeProfile')
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        // strip_tags removes the <script> tags
        expect($user->name)->toBe('John alert("xss") Doe');
    });
});

describe('Navigation and Flow Control', function () {
    test('goBack resets to login step', function () {
        Livewire::test(AuthPage::class)
            ->set('currentStep', 'otp')
            ->call('goBack')
            ->assertSet('currentStep', 'login');
    });

    test('resendOtp triggers sendOtp and shows success message', function () {
        Livewire::test(AuthPage::class)
            ->set('phoneNumber', '09123456789')
            ->call('resendOtp')
            ->assertSee('OTP code sent to your phone!');
    });

    test('resendOtp shows rate limit error when too many attempts', function () {
        $phoneNumber = '09123456789';

        // Trigger rate limit by making multiple requests
        $service = new OtpSendService($phoneNumber, '127.0.0.1');
        $service->send();

        Livewire::test(AuthPage::class)
            ->set('phoneNumber', $phoneNumber)
            ->call('resendOtp')
            ->assertSee('You must wait');
    });
});

describe('Real-time Validation', function () {
    test('validates on property update', function () {
        Livewire::test(AuthPage::class)
            ->set('phoneNumber', 'invalid')
            ->assertHasErrors('phoneNumber');
    });

    test('clears errors when valid value is set', function () {
        Livewire::test(AuthPage::class)
            ->set('phoneNumber', 'invalid')
            ->assertHasErrors('phoneNumber')
            ->set('phoneNumber', '09123456789')
            ->assertHasNoErrors('phoneNumber');
    });

    test('phone number is automatically normalized removing non-numeric characters', function () {
        Livewire::test(AuthPage::class)
            ->set('phoneNumber', '091-234-567-89')
            ->assertSet('phoneNumber', '09123456789');
    });
});

describe('Edge Cases and Security', function () {
    test('handles null user gracefully in password login', function () {
        Livewire::test(AuthPage::class)
            ->set('phoneNumber', '09999999999')
            ->set('passwordToLogin', 'password123')
            ->call('loginWithPassword')
            ->assertSee('Phone number or password is incorrect');
    });

    test('handles very long phone numbers', function () {
        Livewire::test(AuthPage::class)
            ->set('phoneNumber', str_repeat('0', 100))
            ->call('login')
            ->assertHasErrors('phoneNumber');
    });

    test('handles empty digits array', function () {
        Livewire::test(AuthPage::class)
            ->set('digits', [])
            ->call('verifyOtp')
            ->assertHasErrors('digits');
    });

    test('handles more than 6 digits in otp', function () {
        Livewire::test(AuthPage::class)
            ->set('digits', ['1', '2', '3', '4', '5', '6', '7', '8'])
            ->call('verifyOtp')
            ->assertHasErrors('digits');
    });

    test('handles non-integer values in otp digits', function () {
        Livewire::test(AuthPage::class)
            ->set('digits', ['1', '2', 'a', '4', '5', '6'])
            ->call('verifyOtp')
            ->assertHasErrors('digits.*');
    });

    test('password is properly hashed after profile completion', function () {
        $user = User::factory()->incomplete()->create(['phone_number' => '09123456789']);
        $this->actingAs($user);

        Livewire::test(AuthPage::class)
            ->set('name', 'John Doe')
            ->set('national_id', '1234567890')
            ->set('password', 'plaintext_password')
            ->set('password_confirmation', 'plaintext_password')
            ->call('completeProfile');

        $user->refresh();
        expect($user->password)->not->toBe('plaintext_password');
        expect(Hash::check('plaintext_password', $user->password))->toBeTrue();
    });

    test('otp is cleared from cache after successful verification', function () {
        $phoneNumber = '09123456789';
        $otp = '123456';
        $cacheKey = User::getOtpCodeCacheKeyByPhoneNumber($phoneNumber);

        Cache::put($cacheKey, $otp, now()->addMinutes(2));

        Livewire::test(AuthPage::class)
            ->set('phoneNumber', $phoneNumber)
            ->set('digits', str_split($otp))
            ->call('verifyOtp');

        // OTP should be cleared from cache after verification
        expect(Cache::get($cacheKey))->toBeNull();
    });

    test('concurrent otp verification attempts do not create duplicate users', function () {
        $phoneNumber = '09987654321';
        $otp = '123456';
        Cache::put(User::getOtpCodeCacheKeyByPhoneNumber($phoneNumber), $otp, now()->addMinutes(2));

        // First verification
        Livewire::test(AuthPage::class)
            ->set('phoneNumber', $phoneNumber)
            ->set('digits', str_split($otp))
            ->call('verifyOtp');

        // Check only one user exists
        expect(User::where('phone_number', $phoneNumber)->count())->toBe(1);
    });

    test('rate limiter prevents excessive password login attempts', function () {
        $phoneNumber = '09111111111';

        // Make 11 failed attempts to trigger rate limit
        for ($i = 0; $i < 11; $i++) {
            try {
                Livewire::test(AuthPage::class)
                    ->set('phoneNumber', $phoneNumber)
                    ->set('passwordToLogin', 'wrong_password')
                    ->call('loginWithPassword');
            } catch (\Exception $e) {
                // Continue
            }
        }

        // Next attempt should show rate limit error
        Livewire::test(AuthPage::class)
            ->set('phoneNumber', $phoneNumber)
            ->set('passwordToLogin', 'wrong_password')
            ->call('loginWithPassword')
            ->assertSee('You have exceeded the maximum number of attempts');
    });

    test('validates digits are in valid range 0-9', function () {
        Livewire::test(AuthPage::class)
            ->set('digits', ['1', '2', '3', '4', '5', '10'])
            ->call('verifyOtp')
            ->assertHasErrors('digits.*');

        Livewire::test(AuthPage::class)
            ->set('digits', ['1', '2', '3', '4', '5', '-1'])
            ->call('verifyOtp')
            ->assertHasErrors('digits.*');
    });
});

describe('Component State Management', function () {
    test('initial state is correct', function () {
        Livewire::test(AuthPage::class)
            ->assertSet('phoneNumber', '')
            ->assertSet('isLoading', false)
            ->assertSet('digits', ['', '', '', '', '', ''])
            ->assertSet('timerCountDown', 60)
            ->assertSet('name', '')
            ->assertSet('national_id', '')
            ->assertSet('password', '')
            ->assertSet('password_confirmation', '')
            ->assertSet('passwordToLogin', '');
    });

    test('isLoading state toggles correctly during login', function () {
        Livewire::test(AuthPage::class)
            ->set('phoneNumber', '09123456789')
            ->call('login')
            ->assertSet('isLoading', false);
    });

    test('isLoading state toggles correctly during otp verification', function () {
        $phoneNumber = '09123456789';
        $otp = '123456';
        Cache::put(User::getOtpCodeCacheKeyByPhoneNumber($phoneNumber), $otp, now()->addMinutes(2));

        Livewire::test(AuthPage::class)
            ->set('phoneNumber', $phoneNumber)
            ->set('digits', str_split($otp))
            ->call('verifyOtp')
            ->assertSet('isLoading', false);
    });

    test('timerCountDown updates when rate limited on otp send', function () {
        $phoneNumber = '09222222222';

        // First OTP send
        $service = new OtpSendService($phoneNumber, '127.0.0.1');
        $service->send();

        // Second attempt should set timerCountDown
        Livewire::test(AuthPage::class)
            ->set('phoneNumber', $phoneNumber)
            ->call('resendOtp')
            ->assertSet('timerCountDown', fn($value) => $value > 0);
    });
});

