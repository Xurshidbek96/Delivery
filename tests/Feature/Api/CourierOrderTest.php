<?php

namespace Tests\Feature\Api;

use App\Actions\Orders\AssignCourier;
use App\Actions\Orders\ConfirmOrder;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourierOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $courier;

    private User $moderator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->courier = User::factory()->courier()->create();
        $this->moderator = User::factory()->moderator()->create();
    }

    private function assignedOrder(?User $courier = null): Order
    {
        $order = $this->placeOrder();
        app(ConfirmOrder::class)->handle($order, $this->moderator);
        app(AssignCourier::class)->handle($order, $courier ?? $this->courier, $this->moderator);

        return $order->fresh('items');
    }

    public function test_full_delivery_flow(): void
    {
        Storage::fake('public');
        $order = $this->assignedOrder();
        [$potato, $bread] = $order->items;
        $api = $this->actingAsApi($this->courier);

        $api->getJson('/api/v1/courier/orders')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.client.phone', $order->client->phone);

        $api->postJson("/api/v1/courier/orders/{$order->id}/accept")->assertOk()->assertJsonPath('data.status.value', 'shopping');

        $api->patchJson("/api/v1/courier/orders/{$order->id}/items/{$potato->id}", ['status' => 'purchased', 'price' => 12000])
            ->assertOk()->assertJsonPath('data.price', 12000);
        $api->patchJson("/api/v1/courier/orders/{$order->id}/items/{$bread->id}", ['status' => 'unavailable', 'price' => 5000, 'courier_note' => 'Tugagan'])
            ->assertOk()->assertJsonPath('data.price', null);

        $api->post("/api/v1/courier/orders/{$order->id}/purchase-complete", [
            'receipt' => UploadedFile::fake()->image('chek.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'delivering')
            ->assertJsonPath('data.amounts.items_total', 12000)
            ->assertJsonPath('data.amounts.total', 27000);

        $this->assertNotNull($order->fresh()->receipt_path);
        Storage::disk('public')->assertExists($order->fresh()->receipt_path);

        $api->postJson("/api/v1/courier/orders/{$order->id}/deliver")
            ->assertOk()
            ->assertJsonPath('data.status.value', 'delivered')
            ->assertJsonPath('data.payment.status.value', 'paid');

        $this->assertSame(
            ['pending', 'confirmed', 'assigned', 'shopping', 'delivering', 'delivered'],
            $order->histories()->pluck('to_status')->map->value->all(),
        );
    }

    public function test_courier_cannot_touch_orders_of_others(): void
    {
        $other = User::factory()->courier()->create();
        $order = $this->assignedOrder($other);
        $unassigned = $this->placeOrder();

        $api = $this->actingAsApi($this->courier);
        $api->getJson("/api/v1/courier/orders/{$order->id}")->assertNotFound();
        $api->postJson("/api/v1/courier/orders/{$order->id}/accept")->assertNotFound();
        $api->postJson("/api/v1/courier/orders/{$unassigned->id}/accept")->assertNotFound();
        $api->getJson('/api/v1/courier/orders')->assertJsonCount(0, 'data');
    }

    public function test_item_of_another_order_cannot_be_updated(): void
    {
        $order = $this->assignedOrder();
        $foreignItem = $this->assignedOrder(User::factory()->courier()->create())->items->first();
        $this->actingAsApi($this->courier)->postJson("/api/v1/courier/orders/{$order->id}/accept");

        $this->actingAsApi($this->courier)
            ->patchJson("/api/v1/courier/orders/{$order->id}/items/{$foreignItem->id}", ['status' => 'purchased', 'price' => 1000])
            ->assertNotFound();
    }

    public function test_cannot_finish_shopping_with_unmarked_items(): void
    {
        $order = $this->assignedOrder();
        $api = $this->actingAsApi($this->courier);
        $api->postJson("/api/v1/courier/orders/{$order->id}/accept");
        $api->patchJson("/api/v1/courier/orders/{$order->id}/items/{$order->items[0]->id}", ['status' => 'purchased', 'price' => 1000]);

        $api->postJson("/api/v1/courier/orders/{$order->id}/purchase-complete")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Barcha mahsulotlarni belgilang: olindi yoki topilmadi.');
    }

    public function test_purchased_item_requires_price(): void
    {
        $order = $this->assignedOrder();
        $api = $this->actingAsApi($this->courier);
        $api->postJson("/api/v1/courier/orders/{$order->id}/accept");

        $api->patchJson("/api/v1/courier/orders/{$order->id}/items/{$order->items[0]->id}", ['status' => 'purchased'])
            ->assertUnprocessable()->assertJsonValidationErrors('price');
    }

    public function test_items_cannot_be_marked_before_accepting(): void
    {
        $order = $this->assignedOrder();

        $this->actingAsApi($this->courier)
            ->patchJson("/api/v1/courier/orders/{$order->id}/items/{$order->items[0]->id}", ['status' => 'purchased', 'price' => 1000])
            ->assertUnprocessable();
    }

    public function test_receipt_must_be_an_image(): void
    {
        $order = $this->assignedOrder();
        $this->actingAsApi($this->courier)->post("/api/v1/courier/orders/{$order->id}/purchase-complete", [
            'receipt' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('receipt');
    }

    public function test_decline_returns_order_to_moderator(): void
    {
        $order = $this->assignedOrder();

        $this->actingAsApi($this->courier)->postJson("/api/v1/courier/orders/{$order->id}/decline", ['reason' => 'Mashina buzildi'])->assertOk();

        $order->refresh();
        $this->assertSame('confirmed', $order->status->value);
        $this->assertNull($order->courier_id);
        $this->actingAsApi($this->courier)->getJson("/api/v1/courier/orders/{$order->id}")->assertNotFound();
    }

    public function test_courier_updates_online_status_and_location(): void
    {
        $api = $this->actingAsApi($this->courier);

        $api->patchJson('/api/v1/courier/online', ['is_online' => false])->assertOk()->assertJsonPath('data.courier.is_online', false);
        $api->postJson('/api/v1/courier/location', ['lat' => 41.3, 'lng' => 69.2])->assertOk();

        $this->assertEqualsWithDelta(41.3, $this->courier->courierProfile->fresh()->last_lat, 0.0001);
    }

    public function test_courier_is_notified_about_new_assignment(): void
    {
        $order = $this->assignedOrder();

        $this->actingAsApi($this->courier)->getJson('/api/v1/notifications')
            ->assertJsonPath('data.0.title', 'Yangi buyurtma')
            ->assertJsonPath('data.0.order_id', $order->id);
    }
}
