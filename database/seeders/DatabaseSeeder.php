<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $barber = new User([
            'name' => 'JF',
            'email' => 'jf@jfcuts.nl',
            'password' => 'verander-mij-123',
        ]);
        $barber->role = UserRole::Barber;
        $barber->save();

        $shop = $barber->barbershops()->create([
            'name' => 'JF Cuts',
            'description' => 'Professionele barbershop.',
        ]);

        $shop->services()->createMany([
            ['name' => 'Knippen', 'duration' => 30, 'price' => 20],
            ['name' => 'Verven',  'duration' => 60, 'price' => 45],
        ]);

        // 1 = maandag ... 7 = zondag
        $week = [
            1 => ['10:00', '18:00'],
            2 => ['10:00', '18:00'],
            3 => null,                  // gesloten
            4 => ['12:00', '20:00'],
            5 => ['10:00', '18:00'],
            6 => ['09:00', '16:00'],
            7 => null,                  // gesloten
        ];

        foreach ($week as $day => $hours) {
            $shop->availabilityRules()->create([
                'day_of_week' => $day,
                'start_time' => $hours[0] ?? null,
                'end_time' => $hours[1] ?? null,
                'is_available' => $hours !== null,
            ]);
        }
    }
}
