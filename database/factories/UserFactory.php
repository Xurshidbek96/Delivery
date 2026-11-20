<?php

namespace Database\Factories;

use App\Enums\CourierVehicle;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '+99890'.fake()->unique()->numerify('#######'),
            'role' => UserRole::Client,
            'is_active' => true,
        ];
    }

    public function client(): static
    {
        return $this->state(['role' => UserRole::Client]);
    }

    public function courier(bool $online = true): static
    {
        return $this->state(['role' => UserRole::Courier, 'password' => 'secret123'])
            ->afterCreating(fn (User $user) => $user->courierProfile()->create([
                'vehicle' => CourierVehicle::Scooter,
                'is_online' => $online,
            ]));
    }

    public function moderator(): static
    {
        return $this->state(['role' => UserRole::Moderator, 'password' => 'secret123']);
    }

    public function admin(): static
    {
        return $this->state(['role' => UserRole::Admin, 'password' => 'secret123']);
    }

    public function blocked(): static
    {
        return $this->state(['is_active' => false]);
    }
}
