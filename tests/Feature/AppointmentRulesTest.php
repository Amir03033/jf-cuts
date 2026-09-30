<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AppointmentRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_cancel_until_one_hour_before(): void
    {
        $appointment = Appointment::factory()->create([
            'starts_at' => '2026-10-02 15:00:00',
            'ends_at' => '2026-10-02 15:30:00',
        ]);

        Carbon::setTestNow('2026-10-02 14:00:00');   // precies 1 uur ervoor
        $this->assertTrue($appointment->canBeCancelledByCustomer());
        $this->assertTrue($appointment->canBeRescheduledByCustomer());

        Carbon::setTestNow('2026-10-02 14:01:00');   // te laat
        $this->assertFalse($appointment->canBeCancelledByCustomer());
        $this->assertFalse($appointment->canBeRescheduledByCustomer());
    }

    public function test_cancelled_appointment_cannot_be_changed_again(): void
    {
        $appointment = Appointment::factory()->create([
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(3)->addMinutes(30),
        ]);
        $appointment->status = AppointmentStatus::Cancelled;
        $appointment->save();

        $this->assertFalse($appointment->canBeCancelledByCustomer());
    }

    public function test_only_completed_appointments_count(): void
    {
        foreach (AppointmentStatus::cases() as $status) {
            $appointment = Appointment::factory()->create();
            $appointment->status = $status;
            $appointment->save();
        }

        $this->assertSame(1, Appointment::completed()->count());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();   // klok weer normaal
        parent::tearDown();
    }
}
