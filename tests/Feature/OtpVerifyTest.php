<?php

namespace Tests\Feature;

use App\Enums\OTP\Type;
use App\Models\User;
use App\Models\OTP;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


class OtpVerifyTest extends TestCase {
    use RefreshDatabase;

    public function test_it_returns_error_if_mobile_or_code_is_invalid () {
        $response = $this->postJson('/api/otp-verify', [
            'mobile' => '',
            'code'   => '12'
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['mobile', 'code']);
    }


    public function test_it_returns_error_if_mobile_format_is_invalid () {
        $response = $this->postJson('/api/otp-verify', [
            'mobile' => 'abc',
            'code'   => '1234',
        ]);

        $response->assertStatus(422)->assertJsonFragment(['شماره همراه معتبر نیست']);
    }


    public function test_it_returns_error_if_user_not_found () : void {
        $response = $this->postJson('/api/otp-verify', [
            'mobile' => '09120000000',
            'code'   => '1234',
        ]);

        $response->assertStatus(404)->assertJsonFragment(['شماره تلفن صحیح نمی باشد']);
    }


    public function test_it_returns_error_if_otp_is_invalid_or_expired () : void {
        $user = User::factory()->create(['mobile' => '09120000000']);

        OTP::factory()->for($user)->withCode('1234')->expired()->create([
            'type' => Type::Login->value,
        ]);

        $response = $this->postJson('/api/otp-verify', [
            'mobile' => '09120000000',
            'code'   => '1234',
        ]);

        $response->assertStatus(400)->assertJsonFragment(['کد منقضی شده است']);
    }


    public function test_it_verifies_mobile_and_returns_token () : void {
        $user = User::factory()->create([
            'mobile'             => '09120000000',
            'mobile_verified_at' => null,
            'last_name'          => 'فتحی'
        ]);

        $otp = OTP::factory()->for($user)->withCode('1234')->create([
            'type'       => Type::Login->value,
            'expires_at' => now()->addMinutes(5),

        ]);

        $response = $this->postJson('/api/otp-verify', [
            'mobile' => $user->mobile,
            'code'   => '1234',
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['با موفقیت وارد شدید'])
                 ->assertJsonStructure(['data' => ['token', 'page']]);

        $this->assertNotNull($user->fresh()->mobile_verified_at);
    }


    public function test_it_redirects_to_register_page_if_user_has_no_last_name () : void {
        $user = User::factory()->create([
            'mobile'             => '09120000000',
            'mobile_verified_at' => null,
            'last_name'          => null
        ]);

        OTP::factory()->for($user)->withCode('1234')->create([
            'type'       => Type::Login->value,
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/otp-verify', [
            'mobile' => '09120000000',
            'code'   => '1234',
        ]);

        $response->assertStatus(200)->assertJsonFragment(['page' => 'register']);
    }
}
