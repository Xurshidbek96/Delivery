<?php

namespace Tests\Feature\Api;

use App\Actions\Orders\AcceptOrder;
use App\Actions\Orders\AssignCourier;
use App\Actions\Orders\ConfirmOrder;
use App\Models\Address;
use App\Models\User;
use App\Services\Settings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientOrderTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'address' => 'Yunusobod 4, 15-uy',
            'lat' => 41.36,
            'lng' => 69.28,
            'items' => [
                ['name' => 'Kartoshka', 'quantity' => 2, 'unit' => 'kg'],
                ['name' => 'Sut', 'quantity' => 1, 'unit' => 'l', 'note' => '3.2%'],
            ],
        ];
    }

    public function test_client_places_order_and_prices_come_from_server(): void
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAsApi($client)->postJson('/api/v1/client/orders', $this->payload([
            'delivery_fee' => 0,      // e'tiborsiz qoldirilishi kerak
            'total' => 1,
            'items' => [['name' => 'Non', 'quantity' => 2, 'unit' => 'pcs', 'price' => 1]],
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.status.value', 'pending')
            ->assertJsonPath('data.amounts.delivery_fee', 15000)
            ->assertJsonPath('data.amounts.items_total', 0)
            ->assertJsonPath('data.amounts.total', 15000)
            ->assertJsonPath('data.contact.phone', $client->phone)
            ->assertJsonPath('data.items.0.price', null)
            ->assertJsonPath('data.history.0.status.value', 'pending');

        $this->assertMatchesRegularExpression('/^D\d{6}$/', $response->json('data.number'));
    }

    public function test_order_requires_items_and_known_unit(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAsApi($client)->postJson('/api/v1/client/orders', $this->payload(['items' => []]))
            ->assertUnprocessable()->assertJsonValidationErrors('items');

        $this->actingAsApi($client)->postJson('/api/v1/client/orders', $this->payload(['items' => [['name' => 'Olma', 'quantity' => 1, 'unit' => 'tonna']]]))
            ->assertUnprocessable()->assertJsonValidationErrors('items.0.unit');
    }

    public function test_online_payment_is_not_accepted_yet(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAsApi($client)->postJson('/api/v1/client/orders', $this->payload(['payment_method' => 'click']))
            ->assertUnprocessable()->assertJsonValidationErrors('payment_method');
    }

    public function test_saved_address_must_belong_to_client(): void
    {
        $client = User::factory()->client()->create();
        $foreign = Address::query()->create(['user_id' => User::factory()->client()->create()->id, 'address' => 'Begona manzil']);
        $own = $client->addresses()->create(['address' => 'Mening uyim', 'lat' => 41.1, 'lng' => 69.1]);

        $this->actingAsApi($client)->postJson('/api/v1/client/orders', ['address_id' => $foreign->id, 'items' => $this->payload()['items']])
            ->assertUnprocessable()->assertJsonValidationErrors('address_id');

        $this->actingAsApi($client)->postJson('/api/v1/client/orders', ['address_id' => $own->id, 'items' => $this->payload()['items']])
            ->assertCreated()->assertJsonPath('data.address.address', 'Mening uyim');
    }

    public function test_client_sees_only_own_orders(): void
    {
        $client = User::factory()->client()->create();
        $mine = $this->placeOrder($client);
        $other = $this->placeOrder();

        $this->actingAsApi($client)->getJson('/api/v1/client/orders')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->id);

        $this->actingAsApi($client)->getJson("/api/v1/client/orders/{$other->id}")->assertNotFound();
        $this->actingAsApi($client)->postJson("/api/v1/client/orders/{$other->id}/cancel")->assertNotFound();
    }

    public function test_client_cancels_until_shopping_starts(): void
    {
        $client = User::factory()->client()->create();
        $moderator = User::factory()->moderator()->create();
        $courier = User::factory()->courier()->create();

        $order = $this->placeOrder($client);
        $this->actingAsApi($client)->postJson("/api/v1/client/orders/{$order->id}/cancel", ['reason' => 'Kerak emas'])
            ->assertOk()->assertJsonPath('data.status.value', 'cancelled')->assertJsonPath('data.can_cancel', false);

        $order = $this->placeOrder($client);
        app(ConfirmOrder::class)->handle($order, $moderator);
        app(AssignCourier::class)->handle($order, $courier, $moderator);
        app(AcceptOrder::class)->handle($order, $courier);

        $this->actingAsApi($client)->postJson("/api/v1/client/orders/{$order->id}/cancel")->assertUnprocessable();
    }

    public function test_idempotency_key_prevents_duplicate_orders(): void
    {
        $client = User::factory()->client()->create();
        $headers = ['Idempotency-Key' => 'abc-123'];

        $first = $this->actingAsApi($client)->postJson('/api/v1/client/orders', $this->payload(), $headers)->assertCreated();
        $second = $this->actingAsApi($client)->postJson('/api/v1/client/orders', $this->payload(), $headers)->assertOk();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame(1, $client->clientOrders()->count());
    }

    public function test_orders_are_refused_when_acceptance_is_off(): void
    {
        app(SettingsRepository::class)->set(['accepting_orders' => false]);

        $this->actingAsApi(User::factory()->client()->create())
            ->postJson('/api/v1/client/orders', $this->payload())
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Hozir buyurtma qabul qilinmayapti. Birozdan keyin urinib ko\'ring.');
    }

    public function test_client_manages_addresses_but_not_foreign_ones(): void
    {
        $client = User::factory()->client()->create();
        $foreign = Address::query()->create(['user_id' => User::factory()->client()->create()->id, 'address' => 'X']);

        $id = $this->actingAsApi($client)->postJson('/api/v1/client/addresses', ['title' => 'Uy', 'address' => 'Chilonzor'])
            ->assertCreated()->json('data.id');

        $this->actingAsApi($client)->putJson("/api/v1/client/addresses/{$id}", ['address' => 'Chilonzor 2'])->assertOk();
        $this->actingAsApi($client)->putJson("/api/v1/client/addresses/{$foreign->id}", ['address' => 'hack'])->assertNotFound();
        $this->actingAsApi($client)->deleteJson("/api/v1/client/addresses/{$foreign->id}")->assertNotFound();
        $this->assertDatabaseHas('addresses', ['id' => $foreign->id, 'address' => 'X']);
    }

    public function test_client_gets_notification_when_order_is_confirmed(): void
    {
        $client = User::factory()->client()->create();
        $order = $this->placeOrder($client);

        app(ConfirmOrder::class)->handle($order, User::factory()->moderator()->create());

        $this->actingAsApi($client)->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('data.0.title', 'Buyurtma tasdiqlandi')
            ->assertJsonPath('data.0.order_id', $order->id);
    }
}
