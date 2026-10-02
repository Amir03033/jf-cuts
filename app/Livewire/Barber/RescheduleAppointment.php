<?php

namespace App\Livewire\Barber;

use App\Enums\AppointmentStatus;
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
        if ($appointment->barbershop->owner_id !== auth()->id()) {
            abort(403);
        }

        if ($appointment->status !== AppointmentStatus::Scheduled) {
            abort(403, 'Alleen geplande afspraken kunnen worden verplaatst.');
        }

        $this->appointment = $appointment->load([
            'customer',
            'service',
            'barbershop',
            'barbershop.settings',
        ]);
    }

    public function selectDate(string $date): void
    {
        $day = $this->days()->first(
            fn (array $day) => $day['date']->toDateString() === $date
        );

        if (! $day || ! $day['open']) {
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
            $this->error = 'Kies eerst een datum en tijd.';

            return null;
        }

        // Controleer opnieuw of de gekozen tijd nog vrij is.
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
            'De afspraak is verplaatst.'
        );

        return $this->redirectRoute(
            'barber.appointments',
            navigate: true
        );
    }

    /**
     * Beschikbare dagen binnen de boekingsperiode.
     *
     * Deze moet public zijn omdat de Livewire Blade-view
     * deze methode gebruikt.
     */
    public function days(): Collection
    {
        $today = CarbonImmutable::today();

        $maxBookingDays = (int) (
            $this->appointment
                ->barbershop
                ->settings
                ->max_booking_days ?? 30
        );

        return collect(range(0, $maxBookingDays))
            ->map(function (int $i) use ($today) {
                $day = $today->addDays($i);

                return [
                    'date' => $day,
                    'open' => $this->slotsFor($day)->isNotEmpty(),
                ];
            });
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
            'livewire.barber.reschedule-appointment'
        );
    }
}
