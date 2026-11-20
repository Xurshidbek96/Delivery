<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_sees_only_active_products_of_active_shops(): void
    {
        $shop = Shop::factory()->create(['name' => 'Chorsu']);
        $visible = Product::factory()->for($shop)->create(['name' => 'Kartoshka']);
        Product::factory()->for($shop)->inactive()->create();
        Product::factory()->for(Shop::factory()->inactive())->create();

        $client = User::factory()->client()->create();
        $this->actingAsApi($client);

        $this->getJson('/api/v1/client/shops')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Chorsu')
            ->assertJsonPath('data.0.products_count', 1);

        $this->getJson('/api/v1/client/products')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visible->id)
            ->assertJsonPath('data.0.shop.name', 'Chorsu')
            ->assertJsonPath('data.0.unit.value', 'kg');

        $this->getJson("/api/v1/client/products?shop_id={$shop->id}&q=kart")->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/client/products?q=yoq')->assertJsonCount(0, 'data');
    }

    public function test_inactive_shop_and_product_return_404(): void
    {
        $shop = Shop::factory()->inactive()->create();
        $product = Product::factory()->for($shop)->create();
        $hidden = Product::factory()->inactive()->create();

        $this->actingAsApi(User::factory()->client()->create());

        $this->getJson("/api/v1/client/shops/{$shop->id}")->assertNotFound();
        $this->getJson("/api/v1/client/products/{$product->id}")->assertNotFound();
        $this->getJson("/api/v1/client/products/{$hidden->id}")->assertNotFound();
        $this->getJson("/api/v1/client/shops/{$hidden->shop_id}")->assertOk()->assertJsonPath('data.products_count', 0);
    }

    public function test_courier_cannot_use_client_catalog(): void
    {
        $this->actingAsApi(User::factory()->courier()->create())->getJson('/api/v1/client/products')->assertForbidden();
    }

    public function test_order_with_catalog_product_copies_name_unit_and_shop(): void
    {
        $product = Product::factory()->for(Shop::factory()->state(['name' => 'Chorsu']))->create(['name' => 'Kartoshka', 'unit' => 'kg', 'price' => 8000]);
        $client = User::factory()->client()->create();

        $response = $this->actingAsApi($client)->postJson('/api/v1/client/orders', [
            'address' => 'Uychi ko\'chasi, 15-uy',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
                ['name' => 'Non', 'quantity' => 2, 'unit' => 'pcs'],
            ],
        ])->assertCreated();

        $response->assertJsonPath('data.items.0.product_id', $product->id)
            ->assertJsonPath('data.items.0.name', 'Kartoshka')
            ->assertJsonPath('data.items.0.unit.value', 'kg')
            ->assertJsonPath('data.items.0.shop_name', 'Chorsu')
            ->assertJsonPath('data.items.0.price', null)          // haqiqiy narxni kuryer kiritadi
            ->assertJsonPath('data.items.1.product_id', null)
            ->assertJsonPath('data.amounts.items_total', 0);
    }

    public function test_order_with_hidden_product_is_rejected(): void
    {
        $hidden = Product::factory()->inactive()->create();
        $client = User::factory()->client()->create();

        $this->actingAsApi($client)->postJson('/api/v1/client/orders', [
            'address' => 'Uychi ko\'chasi, 15-uy',
            'items' => [['product_id' => $hidden->id, 'quantity' => 1], ['product_id' => 99999, 'quantity' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors(['items.0.product_id', 'items.1.product_id']);

        $this->actingAsApi($client)->postJson('/api/v1/client/orders', [
            'address' => 'Uychi ko\'chasi, 15-uy',
            'items' => [['quantity' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors(['items.0.name', 'items.0.unit']);
    }
}
