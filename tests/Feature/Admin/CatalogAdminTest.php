<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_creates_shop_with_image_and_product(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->moderator()->create());

        $this->post('/admin/shops', [
            'name' => 'Chorsu bozori', 'address' => 'Navoiy ko\'chasi', 'phone' => '90 123 45 67',
            'is_active' => '1', 'image' => UploadedFile::fake()->image('logo.png'),
        ])->assertRedirect()->assertSessionHas('success');

        $shop = Shop::query()->sole();
        $this->assertSame('+998901234567', $shop->phone);
        $this->assertTrue($shop->is_active);
        Storage::disk('public')->assertExists($shop->image_path);

        $this->post('/admin/products', [
            'shop_id' => $shop->id, 'name' => 'Kartoshka', 'unit' => 'kg', 'price' => '8 000', 'is_active' => '1',
        ])->assertRedirect("/admin/shops/{$shop->id}");

        $product = Product::query()->sole();
        $this->assertSame(8000, $product->price);
        $this->assertSame($shop->id, $product->shop_id);
    }

    public function test_unchecked_switch_makes_product_inactive_and_toggle_restores(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $product = Product::factory()->create();

        $this->put("/admin/products/{$product->id}", ['shop_id' => $product->shop_id, 'name' => 'Piyoz', 'unit' => 'kg'])
            ->assertSessionHasNoErrors();
        $this->assertFalse($product->fresh()->is_active);

        $this->post("/admin/products/{$product->id}/toggle")->assertSessionHas('success');
        $this->assertTrue($product->fresh()->is_active);
    }

    public function test_product_validation(): void
    {
        $this->actingAs(User::factory()->moderator()->create());

        $this->post('/admin/products', ['shop_id' => 999, 'name' => '', 'unit' => 'tonna'])
            ->assertSessionHasErrors(['shop_id', 'name', 'unit']);
    }

    public function test_shop_with_products_cannot_be_deleted(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $product = Product::factory()->create();

        $this->delete("/admin/shops/{$product->shop_id}")->assertSessionHas('error');
        $this->assertModelExists($product->shop);

        $this->delete("/admin/products/{$product->id}")->assertRedirect();
        $this->delete("/admin/shops/{$product->shop_id}")->assertRedirect('/admin/shops');
        $this->assertDatabaseCount('shops', 0);
    }

    public function test_catalog_pages_are_staff_only(): void
    {
        $this->actingAs(User::factory()->courier()->create());

        $this->get('/admin/shops')->assertForbidden();
    }
}
