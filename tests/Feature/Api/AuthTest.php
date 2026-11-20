<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_registers_with_otp(): void
    {
        $this->postJson('/api/v1/auth/otp/send', ['phone' => '90 123 45 67'])
            ->assertOk()->assertJsonPath('expires_in', 300);

        $this->postJson('/api/v1/auth/otp/verify', ['phone' => '+998901234567', 'code' => '1111', 'name' => 'Ali'])
            ->assertOk()
            ->assertJsonPath('is_new', true)
            ->assertJsonPath('user.role', 'client')
            ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('users', ['phone' => '+998901234567', 'name' => 'Ali', 'role' => 'client']);
    }

    public function test_wrong_otp_is_rejected_and_code_is_single_use(): void
    {
        $this->postJson('/api/v1/auth/otp/send', ['phone' => '+998901234567'])->assertOk();

        $this->postJson('/api/v1/auth/otp/verify', ['phone' => '+998901234567', 'code' => '9999'])->assertStatus(422);
        $this->postJson('/api/v1/auth/otp/verify', ['phone' => '+998901234567', 'code' => '1111'])->assertOk();
        $this->postJson('/api/v1/auth/otp/verify', ['phone' => '+998901234567', 'code' => '1111'])->assertStatus(422);
    }

    public function test_otp_resend_has_cooldown(): void
    {
        $this->postJson('/api/v1/auth/otp/send', ['phone' => '+998901234567'])->assertOk();
        $this->postJson('/api/v1/auth/otp/send', ['phone' => '+998901234567'])->assertStatus(422);
    }

    public function test_courier_phone_cannot_login_to_client_app(): void
    {
        User::factory()->courier()->create(['phone' => '+998901234567']);
        $this->postJson('/api/v1/auth/otp/send', ['phone' => '+998901234567']);

        $this->postJson('/api/v1/auth/otp/verify', ['phone' => '+998901234567', 'code' => '1111'])->assertForbidden();
    }

    public function test_blocked_client_cannot_login(): void
    {
        User::factory()->client()->blocked()->create(['phone' => '+998901234567']);
        $this->postJson('/api/v1/auth/otp/send', ['phone' => '+998901234567']);

        $this->postJson('/api/v1/auth/otp/verify', ['phone' => '+998901234567', 'code' => '1111'])->assertForbidden();
    }

    public function test_courier_logs_in_with_password(): void
    {
        User::factory()->courier()->create(['phone' => '+998901112233']);

        $this->postJson('/api/v1/auth/courier/login', ['phone' => '901112233', 'password' => 'wrong'])->assertUnauthorized();
        $this->postJson('/api/v1/auth/courier/login', ['phone' => '901112233', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('user.role', 'courier')
            ->assertJsonPath('user.courier.is_online', true);
    }

    public function test_client_cannot_use_courier_login(): void
    {
        User::factory()->create(['phone' => '+998901112233', 'role' => UserRole::Client, 'password' => 'secret123']);

        $this->postJson('/api/v1/auth/courier/login', ['phone' => '+998901112233', 'password' => 'secret123'])->assertUnauthorized();
    }

    public function test_role_middleware_separates_apps(): void
    {
        $courier = User::factory()->courier()->create();
        $this->actingAsApi($courier)->getJson('/api/v1/client/orders')->assertForbidden();

        $client = User::factory()->client()->create();
        $this->actingAsApi($client)->getJson('/api/v1/courier/orders')->assertForbidden();

        $this->actingAsApi($client)->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.role', 'client');
    }

    public function test_staff_tokens_cannot_use_mobile_api(): void
    {
        $this->actingAsApi(User::factory()->admin()->create())->getJson('/api/v1/me')->assertForbidden();
    }

    public function test_validation_messages_are_in_uzbek(): void
    {
        $this->postJson('/api/v1/auth/otp/send', [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.phone.0', 'telefon raqam kiritilishi shart.');
    }

    public function test_not_found_does_not_leak_model_name(): void
    {
        $this->actingAsApi(User::factory()->client()->create())
            ->getJson('/api/v1/client/orders/999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Topilmadi.']);
    }

    public function test_meta_is_public(): void
    {
        $this->getJson('/api/v1/meta')
            ->assertOk()
            ->assertJsonPath('delivery_fee', 15000)
            ->assertJsonPath('payment_methods.0.value', 'cash')
            ->assertJsonCount(1, 'payment_methods');
    }
}
