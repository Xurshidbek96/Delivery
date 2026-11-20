<?php

namespace Tests;

use App\Actions\Orders\PlaceOrder;
use App\Data\PlaceOrderData;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    protected function actingAsApi(User $user): static
    {
        Sanctum::actingAs($user, [$user->role->value]);

        return $this;
    }

    /** Haqiqiy PlaceOrder orqali buyurtma (tarix yozuvi bilan) */
    protected function placeOrder(?User $client = null, array $overrides = []): Order
    {
        $client ??= User::factory()->client()->create();

        return app(PlaceOrder::class)->handle($client, new PlaceOrderData(
            address: $overrides['address'] ?? ['address' => 'Chilonzor 1', 'lat' => 41.3, 'lng' => 69.2],
            items: $overrides['items'] ?? [
                ['name' => 'Kartoshka', 'quantity' => 2, 'unit' => 'kg'],
                ['name' => 'Non', 'quantity' => 3, 'unit' => 'pcs'],
            ],
            paymentMethod: PaymentMethod::Cash,
        ));
    }
}
