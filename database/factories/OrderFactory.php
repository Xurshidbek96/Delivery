<?php

namespace Database\Factories;

use App\Enums\ItemUnit;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => User::factory()->client(),
            'status' => OrderStatus::Pending,
            'address' => fake()->streetAddress(),
            'lat' => 40.998 + fake()->randomFloat(4, -0.05, 0.05),
            'lng' => 71.672 + fake()->randomFloat(4, -0.05, 0.05),
            'contact_name' => fake()->firstName(),
            'contact_phone' => '+99891'.fake()->numerify('#######'),
            'delivery_fee' => 15000,
            'service_fee' => 0,
            'total' => 15000,
            'payment_method' => PaymentMethod::Cash,
            'payment_status' => PaymentStatus::Unpaid,
        ];
    }

    public function status(OrderStatus $status, ?User $courier = null): static
    {
        return $this->state(fn () => array_filter([
            'status' => $status,
            'courier_id' => $courier?->id,
        ], fn ($v) => $v !== null));
    }

    public function withItems(int $count = 3): static
    {
        return $this->afterCreating(function (Order $order) use ($count) {
            foreach (range(1, $count) as $i) {
                $order->items()->create([
                    'name' => fake()->randomElement(['Kartoshka', 'Piyoz', 'Non', 'Sut', 'Tuxum', 'Pomidor', 'Guruch']),
                    'quantity' => fake()->randomElement([1, 2, 0.5, 3]),
                    'unit' => fake()->randomElement(ItemUnit::cases()),
                    'position' => $i,
                ]);
            }
        });
    }
}
