<?php

namespace App\Livewire\Appointments;

use App\Exceptions\BookingException;
use App\Models\Appointment;
use App\Services\AppointmentService;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.customer')]
#[Title('Afspraak verplaatsen')]
class RescheduleAppointment extends Component
{
    public Appointment $appointment;

    public int $step = 1;

    public ?string $date = null;

    public ?string $time = null;

    public ?string $error = null;

    public array $availableSlots = [];

    public function mount(Appointment $appointment): void
    {
        if ($appointment->customer_id !== auth()->id()) {
            abort(403);
        }

        if (! $appointment->canBeRescheduledByCustomer()) {
            abort(403, 'Deze afspraak kan niet meer worden verplaatst.');
        }

        $this->appointment = $appointment->load([
            'service',
            'barbershop',
        ]);
    }

    public function days(): Collection
    {
        $today = CarbonImmutable::today();

        $max = (int) (
            $this->appointment->barbershop
                ->settings
                ->max_booking_days ?? 30
        );

        return collect(range(0, $max))
            ->map(function (int $i) use ($today) {
                $day = $today->addDays($i);

                return [
                    'date' => $day,
                    'open' => $this->slotsFor($day)->isNotEmpty(),
                ];
            });
    }

    public function selectDate(string $date): void
    {
        if (! $this->appointment->canBeRescheduledByCustomer()) {
            $this->error =
                'Deze afspraak kan niet meer worden verplaatst.';

            return;
        }

        $match = $this->days()->first(
            fn ($day) =>
                $day['date']->toDateString() === $date
        );

        if (! $match || ! $match['open']) {
            return;
        }

        $this->date = $date;
        $this->time = null;
        $this->error = null;

        $this->availableSlots = $this->slotsFor(
            CarbonImmutable::parse($date)
        )->all();

        $this->step = 2;
    }

    public function selectTime(string $time): void
    {
        if (! in_array($time, $this->availableSlots, true)) {
            return;
        }

        $this->time = $time;
        $this->error = null;
        $this->step = 3;
    }

    public function back(): void
    {
        $this->error = null;

        if ($this->step === 3) {
            $this->time = null;
            $this->step = 2;

            return;
        }

        if ($this->step === 2) {
            $this->date = null;
            $this->time = null;
            $this->availableSlots = [];
            $this->step = 1;
        }
    }

    public function confirm(): mixed
    {
        if (! $this->date || ! $this->time) {
            $this->error =
                'Kies eerst een datum en tijd.';

            return null;
        }

        if (! $this->appointment->canBeRescheduledByCustomer()) {
            $this->error =
                'Deze afspraak kan niet meer worden verplaatst.';

            return null;
        }

        // Opnieuw controleren op het moment van opslaan.
        $currentSlots = $this->slotsFor(
            CarbonImmutable::parse($this->date)
        )->all();

        if (! in_array($this->time, $currentSlots, true)) {
            $this->error =
                'Deze tijd is niet meer beschikbaar. Kies een andere tijd.';

            $this->availableSlots = $currentSlots;
            $this->time = null;
            $this->step = 2;

            return null;
        }

        $startsAt = CarbonImmutable::parse(
            "{$this->date} {$this->time}",
            config('app.timezone')
        );

        try {
            app(AppointmentService::class)->rescheduleAppointment(
                appointment: $this->appointment,
                newStartsAt: $startsAt,
                actor: auth()->user(),
            );
        } catch (BookingException $e) {
            $this->error = $e->getMessage();

            $this->availableSlots = $this->slotsFor(
                CarbonImmutable::parse($this->date)
            )->all();

            $this->time = null;
            $this->step = 2;

            return null;
        }

        session()->flash(
            'status',
            'Je afspraak is verplaatst.'
        );

        return $this->redirectRoute(
            'customer.appointments',
            navigate: true
        );
    }

    private function slotsFor(CarbonImmutable $day): Collection
    {
        return app(AvailabilityService::class)
            ->getAvailableSlots(
                $this->appointment->barbershop,
                $this->appointment->service,
                $day,
                $this->appointment,
            )
            ->map(
                fn ($slot) => $slot->format('H:i')
            )
            ->values();
    }

    public function render()
    {
        return view(
            'livewire.appointments.reschedule-appointment'
        );
    }
}
