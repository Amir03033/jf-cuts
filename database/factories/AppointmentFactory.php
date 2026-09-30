<?php

namespace Database\Factories;

use App\Models\Barbershop;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDays(2)->setTime(14, 0);

        return [
            'barbershop_id' => Barbershop::factory(),
            'customer_id' => User::factory(),
            'service_id' => Service::factory(),
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMinutes(30),
        ];
    }
}
