<?php

namespace Database\Factories;

use App\Models\Barbershop;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'barbershop_id' => Barbershop::factory(),
            'name' => 'Knippen',
            'duration' => 30,
            'price' => 20,
            'active' => true,
        ];
    }
}
