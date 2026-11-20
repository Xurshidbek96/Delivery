<?php

namespace Database\Factories;

use App\Enums\ItemUnit;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'name' => ucfirst(fake()->word()),
            'unit' => ItemUnit::Kilogram,
            'price' => fake()->numberBetween(5, 100) * 1000,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
