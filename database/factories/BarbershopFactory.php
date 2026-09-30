<?php

namespace Database\Factories;

use App\Models\Barbershop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Barbershop>
 */
class BarbershopFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory()->barber(),
            'name' => fake()->company(),
            'description' => null,
            'address' => null,
        ];
    }
}
