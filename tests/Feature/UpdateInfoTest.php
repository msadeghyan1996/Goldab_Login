<?php

namespace Tests\Feature;

use App\Enums\User\Status;
use App\Helpers\Helper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdateInfoTest extends TestCase {
    use RefreshDatabase;

    public function test_it_returns_validation_error_if_fields_are_missing_or_invalid () {
        $response = $this->postJson('/api/update-info', [
            'mobile'      => '',
            'name'        => '',
            'password'    => '',
            'last_name'   => '',
            'national_id' => '123'
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors([
                'mobile',
                'name',
                'password',
                'last_name',
                'national_id'
            ]);
    }


    public function test_it_returns_warning_if_mobile_is_invalid () {
        $response = $this->postJson('/api/update-info', [
            'mobile'      => 'invalid_mobile',
            'name'        => 'Test',
            'password'    => 'Password123',
            'last_name'   => 'User',
            'national_id' => '0084573219'
        ]);

        $response->assertStatus(422)->assertJsonFragment(['شماره همراه معتبر نیست']);
    }


    public function test_it_returns_warning_if_national_id_is_invalid () {
        $user = User::factory()->create(['mobile' => '09123456789']);

        $response = $this->postJson('/api/update-info', [
            'mobile'      => $user->mobile,
            'name'        => 'Test',
            'password'    => 'Password123',
            'last_name'   => 'User',
            'national_id' => '1234567890' // invalid national code
        ]);

        $response->assertStatus(422)->assertJsonFragment(['شماره کد ملی معتبر نیست']);
    }

    public function test_it_returns_warning_if_user_not_found () {
        $response = $this->postJson('/api/update-info', [
            'mobile'      => '09120000000',
            'name'        => 'Test',
            'password'    => 'Password123',
            'last_name'   => 'User',
            'national_id' => Helper::generate()
        ]);

        $response->assertStatus(404)->assertJsonFragment(['شماره تلفن صحیح نمی باشد']);
    }


    public function test_it_updates_user_info_and_returns_token () {
        $user       = User::factory()->create([
            'mobile' => '09123456789',
            'status' => Status::INACTIVE
        ]);
        $nationalId = Helper::generate();
        $payload    = [
            'mobile'      => $user->mobile,
            'name'        => 'Javad',
            'last_name'   => 'Fathi',
            'password'    => 'Password123',
            'national_id' => $nationalId
        ];

        $response = $this->postJson('/api/update-info', $payload);

        $response->assertStatus(200)
                 ->assertJsonFragment(['اطلاعات با موفقیت ثبت شد'])
                 ->assertJsonStructure(['data' => ['page', 'token']]);

        $user->refresh();
        $this->assertEquals('Javad', $user->name);
        $this->assertEquals('Fathi', $user->last_name);
        $this->assertEquals(Status::ACTIVE, $user->status);
        $this->assertTrue(Hash::check('Password123', $user->password));
        $this->assertEquals($nationalId, $user->national_id);
    }
}
