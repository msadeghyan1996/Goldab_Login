<?php

namespace Tests\Unit\Models;

use App\Models\Enum\OtpPurpose;
use App\Models\OtpToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class OtpTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Date::setTestNow(now());
        Config::set('otp.pepper', 'test-pepper');
    }

    public function test_mutator_sets_64_char_hex_hash_and_32_char_hex_salt_with_pepper_scheme(): void
    {
        $phone = '+989145509000';
        $plainCode = '123456';

        $otp = OtpToken::create([
            'phone' => $phone,
            'code_hash' => $plainCode, // mutator will hash and set salt
            'purpose' => OtpPurpose::REGISTER,
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $otp->code_hash, 'code_hash must be 64-char hex');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $otp->salt, 'salt must be 32-char hex');

        $expected = hash('sha256', config('otp.pepper') . $otp->salt . $plainCode);
        $this->assertSame($expected, $otp->code_hash, 'code_hash must equal sha256(pepper + salt + code)');
    }

    public function test_same_plain_code_uses_random_salt_producing_different_hashes(): void
    {
        $plainCode = '999000';

        $otpA = OtpToken::create([
            'phone' => '+989145509000',
            'code_hash' => $plainCode,
            'purpose' => OtpPurpose::LOGIN,
            'expires_at' => now()->addMinutes(5),
        ]);

        $otpB = OtpToken::create([
            'phone' => '+989145509000',
            'code_hash' => $plainCode,
            'purpose' => OtpPurpose::LOGIN,
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->assertNotSame($otpA->salt, $otpB->salt, 'salts must differ for different tokens');
        $this->assertNotSame($otpA->code_hash, $otpB->code_hash, 'hashes must differ due to different salts');
    }
}


