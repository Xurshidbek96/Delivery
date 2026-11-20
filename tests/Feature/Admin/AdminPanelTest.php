<?php

namespace Tests\Feature\Admin;

use App\Actions\Orders\AssignCourier;
use App\Actions\Orders\ConfirmOrder;
use App\Models\Product;
use App\Models\User;
use App\Services\Settings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_logs_in_with_phone_and_password(): void
    {
        User::factory()->moderator()->create(['phone' => '+998900000002']);

        $this->post('/admin/login', ['phone' => '90 000 00 02', 'password' => 'secret123'])
            ->assertRedirect('/admin');
        $this->assertAuthenticated();
    }

    public function test_couriers_and_clients_cannot_login_to_admin(): void
    {
        User::factory()->courier()->create(['phone' => '+998901110001']);

        $this->post('/admin/login', ['phone' => '+998901110001', 'password' => 'secret123'])->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/orders')->assertRedirect('/admin/login');
    }

    public function test_all_pages_render_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $courier = User::factory()->courier()->create();
        $order = $this->placeOrder();
        app(ConfirmOrder::class)->handle($order, $admin);
        $this->placeOrder();
        $product = Product::factory()->create();

        $this->actingAs($admin);
        foreach ([
            '/admin', '/admin/orders', '/admin/orders?status=pending&q=D0', "/admin/orders/{$order->id}",
            '/admin/couriers', '/admin/couriers/create', "/admin/couriers/{$courier->id}", "/admin/couriers/{$courier->id}/edit",
            '/admin/clients', "/admin/clients/{$order->client_id}", '/admin/staff', '/admin/staff/create',
            '/admin/settings', '/admin/profile',
            '/admin/shops', '/admin/shops?state=active&q=a', '/admin/shops/create', "/admin/shops/{$product->shop_id}", "/admin/shops/{$product->shop_id}/edit",
            '/admin/products', "/admin/products?shop_id={$product->shop_id}&state=visible", '/admin/products/create', "/admin/products/{$product->id}/edit",
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $this->getJson('/admin/orders/pending-count')->assertExactJson(['pending' => 1]);
    }

    public function test_courier_url_does_not_resolve_other_roles(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $client = User::factory()->client()->create();

        $this->get("/admin/couriers/{$client->id}")->assertNotFound();
    }

    public function test_moderator_confirms_and_assigns_courier(): void
    {
        $moderator = User::factory()->moderator()->create();
        $courier = User::factory()->courier()->create();
        $order = $this->placeOrder();

        $this->actingAs($moderator)
            ->post("/admin/orders/{$order->id}/confirm", ['delivery_fee' => 20000])
            ->assertSessionHas('success');

        $this->assertSame(20000, $order->fresh()->delivery_fee);
        $this->assertSame(20000, $order->fresh()->total);
        $this->assertSame($moderator->id, $order->fresh()->confirmed_by);

        $this->post("/admin/orders/{$order->id}/assign", ['courier_id' => $courier->id])->assertSessionHas('success');
        $this->assertSame('assigned', $order->fresh()->status->value);
        $this->assertSame($courier->id, $order->fresh()->courier_id);
    }

    public function test_cannot_assign_before_confirmation(): void
    {
        $moderator = User::factory()->moderator()->create();
        $courier = User::factory()->courier()->create();
        $order = $this->placeOrder();

        $this->actingAs($moderator)->post("/admin/orders/{$order->id}/assign", ['courier_id' => $courier->id])
            ->assertSessionHas('error');
        $this->assertSame('pending', $order->fresh()->status->value);
    }

    public function test_courier_capacity_limit(): void
    {
        app(SettingsRepository::class)->set(['courier_max_active_orders' => 1]);
        $moderator = User::factory()->moderator()->create();
        $courier = User::factory()->courier()->create();

        $first = $this->placeOrder();
        app(ConfirmOrder::class)->handle($first, $moderator);
        app(AssignCourier::class)->handle($first, $courier, $moderator);

        $second = $this->placeOrder();
        app(ConfirmOrder::class)->handle($second, $moderator);

        $this->actingAs($moderator)->post("/admin/orders/{$second->id}/assign", ['courier_id' => $courier->id])->assertSessionHas('error');
        $this->assertNull($second->fresh()->courier_id);
    }

    public function test_reassign_changes_courier(): void
    {
        $moderator = User::factory()->moderator()->create();
        [$a, $b] = User::factory()->courier()->count(2)->create();
        $order = $this->placeOrder();
        app(ConfirmOrder::class)->handle($order, $moderator);
        app(AssignCourier::class)->handle($order, $a, $moderator);

        $this->actingAs($moderator)->post("/admin/orders/{$order->id}/assign", ['courier_id' => $b->id])->assertSessionHas('success');
        $this->assertSame($b->id, $order->fresh()->courier_id);
        $this->assertSame('assigned', $order->fresh()->status->value);
    }

    public function test_blocked_courier_cannot_be_assigned(): void
    {
        $moderator = User::factory()->moderator()->create();
        $courier = User::factory()->courier()->blocked()->create();
        $order = $this->placeOrder();
        app(ConfirmOrder::class)->handle($order, $moderator);

        $this->actingAs($moderator)->post("/admin/orders/{$order->id}/assign", ['courier_id' => $courier->id])->assertSessionHasErrors('courier_id');
    }

    public function test_moderator_cancels_with_reason(): void
    {
        $moderator = User::factory()->moderator()->create();
        $order = $this->placeOrder();

        $this->actingAs($moderator)->post("/admin/orders/{$order->id}/cancel", [])->assertSessionHasErrors('reason');
        $this->post("/admin/orders/{$order->id}/cancel", ['reason' => 'Hududdan tashqari'])->assertSessionHas('success');

        $this->assertSame('cancelled', $order->fresh()->status->value);
        $this->assertSame('moderator', $order->fresh()->cancelled_by_role->value);
    }

    public function test_moderator_cannot_manage_staff_or_settings(): void
    {
        $this->actingAs(User::factory()->moderator()->create());

        $this->get('/admin/settings')->assertForbidden();
        $this->get('/admin/staff')->assertForbidden();
        $this->post('/admin/staff', ['name' => 'X', 'phone' => '+998901111111', 'role' => 'admin', 'password' => 'password1'])->assertForbidden();
    }

    public function test_admin_creates_courier_who_can_login_to_app(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/couriers', ['name' => 'Yangi Kuryer', 'phone' => '93 123 45 67', 'password' => 'kuryer1', 'vehicle' => 'car', 'is_active' => '1'])
            ->assertRedirect();

        $this->postJson('/api/v1/auth/courier/login', ['phone' => '+998931234567', 'password' => 'kuryer1'])
            ->assertOk()->assertJsonPath('user.courier.vehicle.value', 'car');
    }

    public function test_blocking_courier_revokes_tokens(): void
    {
        $courier = User::factory()->courier()->create();
        $courier->createToken('app', ['courier']);

        $this->actingAs(User::factory()->admin()->create())->put("/admin/couriers/{$courier->id}", [
            'name' => $courier->name, 'phone' => $courier->phone, 'vehicle' => 'foot',
        ])->assertRedirect();

        $this->assertFalse($courier->fresh()->is_active);
        $this->assertSame(0, $courier->tokens()->count());
    }

    public function test_admin_updates_settings(): void
    {
        $this->actingAs(User::factory()->admin()->create())->put('/admin/settings', [
            'delivery_fee' => 18000, 'service_fee' => 2000, 'courier_max_active_orders' => 2,
            'max_items_per_order' => 30, 'support_phone' => '+998 71 200 00 00', 'accepting_orders' => '1',
        ])->assertSessionHas('success');

        $this->getJson('/api/v1/meta')->assertJsonPath('delivery_fee', 18000)->assertJsonPath('service_fee', 2000);
        $this->assertSame(20000, $this->placeOrder()->total);
    }

    public function test_admin_cannot_demote_self(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put("/admin/staff/{$admin->id}", [
            'name' => $admin->name, 'phone' => $admin->phone, 'role' => 'moderator', 'is_active' => '1',
        ])->assertSessionHas('error');
        $this->assertTrue($admin->fresh()->isAdmin());
    }
}
