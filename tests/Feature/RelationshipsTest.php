<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Barbershop;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_has_services_and_rules(): void
    {
        $shop = Barbershop::factory()->create();

        $shop->services()->create(['name' => 'Knippen', 'duration' => 30, 'price' => 20]);
        $shop->availabilityRules()->create([
            'day_of_week' => 1, 'start_time' => '10:00', 'end_time' => '18:00',
        ]);
        $shop->availabilityExceptions()->create(['date' => '2026-10-23', 'is_available' => false]);
        $shop->images()->create(['path' => 'shops/1.jpg', 'sort_order' => 1]);

        $this->assertCount(1, $shop->services);
        $this->assertCount(1, $shop->availabilityRules);
        $this->assertCount(1, $shop->availabilityExceptions);
        $this->assertCount(1, $shop->images);
    }

    public function test_appointment_links_shop_customer_and_service(): void
    {
        $shop = Barbershop::factory()->create();
        $service = Service::factory()->for($shop)->create();
        $customer = User::factory()->create();

        $appointment = $shop->appointments()->create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(30),
        ]);

        $this->assertTrue($appointment->barbershop->is($shop));
        $this->assertTrue($appointment->customer->is($customer));
        $this->assertTrue($appointment->service->is($service));
        $this->assertCount(1, $customer->appointments);
        $this->assertSame(AppointmentStatus::Scheduled, $appointment->fresh()->status);
    }

    public function test_status_cannot_be_mass_assigned(): void
    {
        $shop = Barbershop::factory()->create();
        $service = Service::factory()->for($shop)->create();
        $customer = User::factory()->create();

        $appointment = $shop->appointments()->create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(30),
            'status' => 'completed',       // wordt genegeerd
            'amount_paid' => 999,          // wordt genegeerd
        ]);

        $this->assertSame(AppointmentStatus::Scheduled, $appointment->fresh()->status);
        $this->assertNull($appointment->fresh()->amount_paid);
    }

    public function test_one_rule_per_day_per_shop(): void
    {
        $shop = Barbershop::factory()->create();
        $shop->availabilityRules()->create(['day_of_week' => 1]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $shop->availabilityRules()->create(['day_of_week' => 1]);
    }
}
