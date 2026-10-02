<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Exceptions\BookingException;
use App\Models\Appointment;
use App\Models\Barbershop;
use App\Models\Service;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\AvailabilityService;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Throwable;

class AppointmentEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $barber;
    private Barbershop $shop;
    private Service $knippen;
    private Service $verven;
    private AvailabilityService $availability;
    private AppointmentService $appointments;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-30 09:00:00');   // woensdag

        $this->barber = User::factory()->barber()->create();
        $this->shop = $this->barber->barbershops()->create(['name' => 'JF Cuts']);
        $this->knippen = $this->shop->services()->create(['name' => 'Knippen', 'duration' => 30, 'price' => 20]);
        $this->verven = $this->shop->services()->create(['name' => 'Verven', 'duration' => 60, 'price' => 45]);

        // vrijdag 10:00-18:00, woensdag gesloten
        $this->shop->availabilityRules()->create([
            'day_of_week' => 5, 'start_time' => '10:00', 'end_time' => '18:00', 'is_available' => true,
        ]);
        $this->shop->availabilityRules()->create(['day_of_week' => 3, 'is_available' => false]);

        $this->availability = app(AvailabilityService::class);
        $this->appointments = app(AppointmentService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ---------- hulpmethodes ----------

    private function slots(Service $service, string $date): array
    {
        return $this->availability
            ->getAvailableSlots($this->shop, $service, Carbon::parse($date))
            ->map(fn ($slot) => $slot->format('H:i'))
            ->all();
    }

    private function book(User $customer, Service $service, string $start): Appointment
    {
        return $this->appointments->createAppointment(
            $this->shop, $customer, $service, Carbon::parse($start), $customer
        );
    }

    private function assertFails(Closure $action, string $expected): void
    {
        try {
            $action();
        } catch (Throwable $e) {
            $this->assertInstanceOf($expected, $e);

            return;
        }

        $this->fail("Verwachtte een $expected.");
    }

    // ---------- beschikbare tijden ----------

    public function test_knippen_slots_follow_opening_hours(): void
    {
        $slots = $this->slots($this->knippen, '2026-10-02');   // vrijdag

        $this->assertCount(16, $slots);
        $this->assertSame('10:00', $slots[0]);
        $this->assertSame('17:30', end($slots));
    }

    public function test_verven_must_fit_before_closing_time(): void
    {
        $slots = $this->slots($this->verven, '2026-10-02');

        $this->assertSame('17:00', end($slots));
        $this->assertNotContains('17:30', $slots);
    }

    public function test_closed_days_have_no_slots(): void
    {
        $this->assertSame([], $this->slots($this->knippen, '2026-10-07'));   // woensdag: gesloten
        $this->assertSame([], $this->slots($this->knippen, '2026-10-04'));   // zondag: geen rooster
    }

    public function test_exceptions_override_the_weekly_schedule(): void
    {
        $this->shop->availabilityExceptions()->create([
            'date' => '2026-10-16', 'start_time' => '12:00', 'end_time' => '18:00', 'is_available' => true,
        ]);
        $this->shop->availabilityExceptions()->create(['date' => '2026-10-23', 'is_available' => false]);

        $this->assertSame('12:00', $this->slots($this->knippen, '2026-10-16')[0]);
        $this->assertSame([], $this->slots($this->knippen, '2026-10-23'));
    }

    public function test_only_dates_within_the_booking_window_can_be_booked(): void
    {
        $this->assertNotEmpty($this->slots($this->knippen, '2026-10-30'));   // precies 30 dagen vooruit
        $this->assertSame([], $this->slots($this->knippen, '2026-11-06'));   // te ver vooruit
        $this->assertSame([], $this->slots($this->knippen, '2026-09-25'));   // verleden
    }

    public function test_times_that_already_passed_today_are_not_offered(): void
    {
        Carbon::setTestNow('2026-10-02 12:10:00');

        $this->assertSame('12:30', $this->slots($this->knippen, '2026-10-02')[0]);
    }

    public function test_existing_appointments_block_overlapping_slots(): void
    {
        $this->book(User::factory()->create(), $this->knippen, '2026-10-02 14:00');

        $knippen = $this->slots($this->knippen, '2026-10-02');
        $this->assertNotContains('14:00', $knippen);
        $this->assertContains('13:30', $knippen);
        $this->assertContains('14:30', $knippen);

        $verven = $this->slots($this->verven, '2026-10-02');
        $this->assertNotContains('13:30', $verven);   // 13:30-14:30 overlapt
        $this->assertNotContains('14:00', $verven);
        $this->assertContains('13:00', $verven);      // 13:00-14:00 past net wel
        $this->assertContains('14:30', $verven);
    }

    // ---------- afspraak maken ----------

    public function test_customer_can_book_an_available_slot(): void
    {
        $customer = User::factory()->create();

        $appointment = $this->book($customer, $this->knippen, '2026-10-02 14:00');

        $this->assertSame('14:30', $appointment->ends_at->format('H:i'));
        $this->assertSame(AppointmentStatus::Scheduled, $appointment->fresh()->status);
    }

    public function test_double_booking_is_refused(): void
    {
        $this->book(User::factory()->create(), $this->knippen, '2026-10-02 14:00');

        $this->assertFails(
            fn () => $this->book(User::factory()->create(), $this->knippen, '2026-10-02 14:00'),
            BookingException::class
        );
        $this->assertDatabaseCount('Appointments', 1);
    }

    public function test_times_between_the_intervals_are_refused(): void
    {
        $this->assertFails(
            fn () => $this->book(User::factory()->create(), $this->knippen, '2026-10-02 14:15'),
            BookingException::class
        );
    }

    public function test_a_customer_can_have_multiple_future_appointments(): void
    {
        $customer = User::factory()->create();

        $this->book($customer, $this->knippen, '2026-10-02 14:00');
        $this->book($customer, $this->verven, '2026-10-09 15:30');

        $this->assertCount(2, $customer->appointments);
    }

    public function test_customer_cannot_book_for_someone_else(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $this->assertFails(
            fn () => $this->appointments->createAppointment(
                $this->shop, $a, $this->knippen, Carbon::parse('2026-10-02 14:00'), $b
            ),
            AuthorizationException::class
        );
    }

    public function test_barber_can_book_for_an_existing_customer(): void
    {
        $customer = User::factory()->create();

        $appointment = $this->appointments->createAppointment(
            $this->shop, $customer, $this->knippen, Carbon::parse('2026-10-02 15:30'), $this->barber
        );

        $this->assertSame($customer->id, $appointment->customer_id);
    }

    // ---------- annuleren en verplaatsen ----------

    public function test_cancelling_frees_the_slot(): void
    {
        $customer = User::factory()->create();
        $appointment = $this->book($customer, $this->knippen, '2026-10-02 14:00');

        $this->appointments->cancelAppointment($appointment, $customer);

        $this->assertSame(AppointmentStatus::Cancelled, $appointment->fresh()->status);
        $this->assertContains('14:00', $this->slots($this->knippen, '2026-10-02'));
    }

    public function test_customer_cannot_cancel_within_one_hour_but_barber_can(): void
    {
        $customer = User::factory()->create();
        $appointment = $this->book($customer, $this->knippen, '2026-10-02 15:00');

        Carbon::setTestNow('2026-10-02 14:01:00');

        $this->assertFails(
            fn () => $this->appointments->cancelAppointment($appointment, $customer),
            BookingException::class
        );

        $this->appointments->cancelAppointment($appointment, $this->barber);
        $this->assertSame(AppointmentStatus::Cancelled, $appointment->fresh()->status);
    }

    public function test_someone_elses_appointment_cannot_be_cancelled(): void
    {
        $appointment = $this->book(User::factory()->create(), $this->knippen, '2026-10-02 15:00');

        $this->assertFails(
            fn () => $this->appointments->cancelAppointment($appointment, User::factory()->create()),
            AuthorizationException::class
        );
    }

    public function test_customer_cannot_reschedule_within_one_hour_but_barber_can(): void
    {
        $customer = User::factory()->create();
        $appointment = $this->book($customer, $this->knippen, '2026-10-02 15:00');

        Carbon::setTestNow('2026-10-02 14:01:00');

        $this->assertFails(
            fn () => $this->appointments->rescheduleAppointment($appointment, Carbon::parse('2026-10-02 16:00'), $customer),
            BookingException::class
        );

        $moved = $this->appointments->rescheduleAppointment(
            $appointment, Carbon::parse('2026-10-02 16:00'), $this->barber
        );

        $this->assertSame('16:00', $moved->starts_at->format('H:i'));
        $this->assertSame('16:30', $moved->ends_at->format('H:i'));
    }

    public function test_reschedule_cannot_take_an_occupied_slot(): void
    {
        $customer = User::factory()->create();
        $this->book(User::factory()->create(), $this->knippen, '2026-10-02 16:00');
        $appointment = $this->book($customer, $this->knippen, '2026-10-02 15:00');

        $this->assertFails(
            fn () => $this->appointments->rescheduleAppointment($appointment, Carbon::parse('2026-10-02 16:00'), $customer),
            BookingException::class
        );
    }

    // ---------- afronden en no-show ----------

    public function test_barber_completes_with_the_amount_actually_received(): void
    {
        $appointment = $this->book(User::factory()->create(), $this->knippen, '2026-10-02 14:00');

        Carbon::setTestNow('2026-10-02 14:31:00');
        $this->appointments->completeAppointment($appointment, $this->barber, 18);

        $fresh = $appointment->fresh();
        $this->assertSame(AppointmentStatus::Completed, $fresh->status);
        $this->assertSame('18.00', $fresh->amount_paid);   // niet de standaardprijs van €20
        $this->assertNotNull($fresh->completed_at);
    }

    public function test_customer_cannot_complete_or_mark_no_show(): void
    {
        $customer = User::factory()->create();
        $appointment = $this->book($customer, $this->knippen, '2026-10-02 14:00');

        Carbon::setTestNow('2026-10-02 14:31:00');

        $this->assertFails(
            fn () => $this->appointments->completeAppointment($appointment, $customer, 20),
            AuthorizationException::class
        );
        $this->assertFails(
            fn () => $this->appointments->markAsNoShow($appointment, $customer),
            AuthorizationException::class
        );
    }

    public function test_future_appointment_cannot_be_completed(): void
    {
        $appointment = $this->book(User::factory()->create(), $this->knippen, '2026-10-02 14:00');

        $this->assertFails(
            fn () => $this->appointments->completeAppointment($appointment, $this->barber, 20),
            BookingException::class
        );
    }

    public function test_no_show_gives_no_amount_and_frees_nothing_to_complete(): void
    {
        $appointment = $this->book(User::factory()->create(), $this->knippen, '2026-10-02 14:00');

        Carbon::setTestNow('2026-10-02 14:31:00');
        $this->appointments->markAsNoShow($appointment, $this->barber);

        $fresh = $appointment->fresh();
        $this->assertSame(AppointmentStatus::NoShow, $fresh->status);
        $this->assertNull($fresh->amount_paid);

        $this->assertFails(
            fn () => $this->appointments->completeAppointment($appointment, $this->barber, 20),
            BookingException::class
        );
    }
}
