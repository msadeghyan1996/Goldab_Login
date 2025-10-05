<?php

namespace Tests\Feature;

use App\Enums\OTP\Type;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase {
    use RefreshDatabase;

    public function test_returns_validation_error_when_mobile_is_empty(): void {
        $response = $this->postJson('/api/login', ['mobile' => '']);

        $response->assertStatus(422)->assertJsonStructure([
            'success',
            'statusType',
            'title',
            'message',
            'errors',
            'notify'
        ])->assertJsonFragment(['success' => false])->assertJsonFragment(['statusType' => 'warning']);
    }

    public function test_returns_warning_when_mobile_is_invalid(): void {
        $response = $this->postJson('/api/login', ['mobile' => '12345']);

        $response->assertStatus(422)
                 ->assertJsonFragment(['success' => false])
                 ->assertJsonFragment(['statusType' => 'warning'])
                 ->assertJsonPath('errors.mobile', 'شماره همراه صحیح نمی باشد');
    }

    public function test_creates_user_and_otp_for_new_mobile(): void {
        $mobile = '09123456789';

        $response = $this->postJson('/api/login', ['mobile' => $mobile]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['success' => true])
                 ->assertJsonPath('data.page', 'validation_code');

        $this->assertDatabaseHas('users', ['mobile' => $mobile]);
        $user = User::where('mobile', $mobile)->first();
        $this->assertDatabaseHas('otps', ['user_id' => $user->id, 'type' => Type::Login->value]);
    }

    public function test_returns_warning_if_existing_user_without_last_name_has_active_otp(): void {
        $user = User::factory()->create(['mobile' => '09123456789', 'last_name' => null, 'mobile_verified_at' => null]);

        $otp = $user->otp()->create([
            'code'       => '1234',
            'type'       => Type::Login->value,
            'expires_at' => now()->addMinutes(3),
        ]);

        $response = $this->postJson('/api/login', ['mobile' => $user->mobile]);

        $response->assertStatus(400)
                 ->assertJsonFragment(['success' => false])
                 ->assertJsonFragment(['statusType' => 'warning'])
                 ->assertJsonPath('errors.secondsLeft', (int) abs($otp->expires_at->diffInSeconds(now())));
    }

    public function test_generates_new_otp_if_existing_user_without_last_name_has_expired_otp(): void {
        $user = User::factory()->create(['mobile' => '09123456789', 'last_name' => null,'mobile_verified_at' => null]);
        $otp  = $user->otp()->create([
            'code'       => '1234',
            'type'       => Type::Login->value,
            'expires_at' => now()->subMinutes(1),
        ]);

        $response = $this->postJson('/api/login', ['mobile' => $user->mobile]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['success' => true])
                 ->assertJsonPath('data.page', 'validation_code');

        $user->refresh();
        $this->assertNotEquals('1234', $user->otp->code);
    }

    public function test_prompts_registration_if_user_without_last_name_and_no_verification_code(): void {
        $user = User::factory()->create([
            'mobile'            => '09123456789',
            'last_name'         => null,
            'mobile_verified_at' => null
        ]);

        // force OTP table empty
        $this->assertNull($user->otp);

        $response = $this->postJson('/api/login', ['mobile' => $user->mobile]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['success' => true])
                 ->assertJsonPath('data.page', 'validation_code');
    }

    public function test_logs_in_existing_user_with_last_name(): void {
        $user = User::factory()->create([
            'mobile'    => '09123456789',
            'last_name' => 'Test'
        ]);

        $response = $this->postJson('/api/login', ['mobile' => $user->mobile]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['success' => true])
                 ->assertJsonPath('data.page', 'home')
                 ->assertJsonStructure(['data' => ['token', 'page']]);
    }
}
