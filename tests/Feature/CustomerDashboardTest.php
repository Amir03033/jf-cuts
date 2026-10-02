<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Barbershop;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CustomerDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-30 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function appointmentFor(User $customer, string $start, ?AppointmentStatus $status = null): Appointment
    {
        $service = Service::factory()->create(['name' => 'Knippen', 'price' => 20]);

        return Appointment::factory()->create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'barbershop_id' => $service->barbershop_id,
            'starts_at' => $start,
            'ends_at' => Carbon::parse($start)->addMinutes(30),
            'status' => $status ?? AppointmentStatus::Scheduled,
        ]);
    }

    public function test_customer_sees_greeting_and_next_appointment(): void
    {
        $customer = User::factory()->create(['name' => 'Amir Jebbari']);
        $this->appointmentFor($customer, '2026-10-02 15:30:00');

        $this->actingAs($customer)->get('/customer/dashboard')
            ->assertOk()
            ->assertSee('Goedemorgen, Amir')
            ->assertSee('Vrijdag 2 oktober')
            ->assertSee('15:30')
            ->assertSee('Knippen')
            ->assertSee('€20');
    }

    public function test_next_appointment_is_the_earliest_upcoming_one(): void
    {
        $customer = User::factory()->create();
        $this->appointmentFor($customer, '2026-10-10 15:30:00');
        $soonest = $this->appointmentFor($customer, '2026-10-02 14:00:00');

        $response = $this->actingAs($customer)->get('/customer/dashboard');

        $this->assertTrue($response->viewData('nextAppointment')->is($soonest));
    }

    public function test_cancelled_and_other_peoples_appointments_are_not_shown(): void
    {
        $customer = User::factory()->create();
        $this->appointmentFor($customer, '2026-10-02 14:00:00', AppointmentStatus::Cancelled);
        $this->appointmentFor(User::factory()->create(), '2026-10-03 14:00:00');

        $response = $this->actingAs($customer)->get('/customer/dashboard');

        $this->assertNull($response->viewData('nextAppointment'));
        $response->assertSee('Je hebt geen geplande afspraken.');
    }

    public function test_only_completed_appointments_count_as_visits(): void
    {
        $customer = User::factory()->create();
        $this->appointmentFor($customer, '2026-09-10 14:00:00', AppointmentStatus::Completed);
        $this->appointmentFor($customer, '2026-09-12 14:00:00', AppointmentStatus::Completed);
        $this->appointmentFor($customer, '2026-09-14 14:00:00', AppointmentStatus::Cancelled);
        $this->appointmentFor($customer, '2026-09-16 14:00:00', AppointmentStatus::NoShow);
        $this->appointmentFor(User::factory()->create(), '2026-09-18 14:00:00', AppointmentStatus::Completed);

        $response = $this->actingAs($customer)->get('/customer/dashboard');

        $this->assertSame(2, $response->viewData('visits'));
    }

    public function test_placeholder_pages_open_for_customers_only(): void
    {

        $customer = User::factory()->create();

        Barbershop::factory()->create();

        foreach ([
                     '/customer/appointments',
                     '/customer/appointments/new',
                     '/customer/profile',
                 ] as $url) {
            $this->actingAs($customer)
                ->get($url)
                ->assertOk();
        }

        $this->actingAs(User::factory()->barber()->create())
            ->get('/customer/appointments')
            ->assertForbidden();
    }
}
