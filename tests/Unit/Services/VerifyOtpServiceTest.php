<?php

namespace Tests\Unit\Services;

use App\Models\Enum\OtpPurpose;
use App\Models\OtpToken;
use App\Services\Otp\OtpCache;
use App\Services\Otp\VerifyOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class VerifyOtpServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Date::setTestNow(now());
        Config::set('otp.pepper', 'test-pepper');
    }

    public function test_set_phone_loads_latest_active_token_excluding_consumed_and_expired(): void
    {
        $phone = '+989145509000';

        // Active older
        $activeOld = OtpToken::create([
            'phone' => $phone,
            'code_hash' => '111111', // raw; mutator hashes and sets salt
            'purpose' => OtpPurpose::REGISTER,
            'expires_at' => now()->addMinutes(10),
        ]);

        // Expired (higher id)
        OtpToken::create([
            'phone' => $phone,
            'code_hash' => '222222',
            'purpose' => OtpPurpose::REGISTER,
            'expires_at' => now()->subMinute(),
        ]);

        // Consumed (higher id)
        OtpToken::create([
            'phone' => $phone,
            'code_hash' => '333333',
            'purpose' => OtpPurpose::REGISTER,
            'expires_at' => now()->addMinutes(10),
            'consumed_at' => now(),
        ]);

        // Active newer (highest id among active)
        $activeNew = OtpToken::create([
            'phone' => $phone,
            'code_hash' => '444444',
            'purpose' => OtpPurpose::REGISTER,
            'expires_at' => now()->addMinutes(10),
        ]);

        $service = new VerifyOtpService();
        $service->setPhone($phone);

        $loaded = $service->getOtpToken();
        $this->assertNotNull($loaded);
        $this->assertSame($activeNew->id, $loaded->id);
    }

    public function test_attempt_increments_attempts_count(): void
    {
        $phone = '+989145509000';

        $token = OtpToken::create([
            'phone' => $phone,
            'code_hash' => '123456',
            'purpose' => OtpPurpose::LOGIN,
            'expires_at' => now()->addMinutes(5),
        ]);

        $service = new VerifyOtpService();
        $service->setPhone($phone)->attempt();

        $token->refresh();
        $this->assertSame(1, $token->attempts_count);
    }

    public function test_code_is_valid_true_and_false(): void
    {
        $phone = '+989145509000';
        $correctCode = '654321';

        OtpToken::create([
            'phone' => $phone,
            'code_hash' => $correctCode, // mutator applies pepper+salt hashing
            'purpose' => OtpPurpose::LOGIN,
            'expires_at' => now()->addMinutes(5),
        ]);

        $service = new VerifyOtpService();
        $service->setPhone($phone);

        $this->assertTrue($service->codeIsValid($correctCode));
        $this->assertFalse($service->codeIsValid('000000'));
    }

    public function test_consume_sets_consumed_at_and_forgets_cache(): void
    {
        $phone = '+989145509000';

        $token = OtpToken::create([
            'phone' => $phone,
            'code_hash' => '777777',
            'purpose' => OtpPurpose::REGISTER,
            'expires_at' => now()->addMinutes(5),
        ]);

        // Seed cache to ensure consume() forgets it
        OtpCache::set($token);
        $this->assertNotNull(OtpCache::get($phone));

        $service = new VerifyOtpService();
        $service->setPhone($phone)->consume();

        $token->refresh();
        $this->assertNotNull($token->consumed_at);

        $this->assertNull(OtpCache::get($phone));
    }
}


