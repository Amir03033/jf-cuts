<?php

namespace App\Livewire\Appointments;

use App\Exceptions\BookingException;
use App\Models\Barbershop;
use App\Models\Service;
use App\Services\AppointmentService;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.customer')]
#[Title('Nieuwe afspraak')]
class CreateAppointment extends Component
{
    // 1 = behandeling, 2 = datum, 3 = tijd, 4 = bevestigen
    public int $step = 1;

    public ?int $serviceId = null;

    public ?string $date = null;

    public ?string $time = null;

    public ?string $error = null;

    /**
     * Beschikbare tijden voor de geselecteerde datum.
     *
     * Dit is bewust een normale array en geen Computed property,
     * zodat Livewire deze waarde probleemloos kan hydrateren.
     *
     * @var array<int, string>
     */
    public array $availableSlots = [];

    #[Computed]
    public function barbershop(): Barbershop
    {
        return Barbershop::query()
            ->orderBy('id')
            ->firstOrFail();
    }

    #[Computed]
    public function services(): Collection
    {
        return $this->barbershop
            ->services()
            ->where('active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function selectedService(): ?Service
    {
        if (! $this->serviceId) {
            return null;
        }

        return $this->services->firstWhere(
            'id',
            $this->serviceId
        );
    }

    #[Computed]
    public function days(): Collection
    {
        if (! $this->selectedService) {
            return collect();
        }

        $today = CarbonImmutable::today();

        $max = (int) (
            $this->barbershop->settings->max_booking_days ?? 30
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

    public function selectService(int $id): void
    {
        abort_unless(
            $this->services->contains('id', $id),
            422
        );

        $this->serviceId = $id;

        $this->reset(
            'date',
            'time',
            'error'
        );

        $this->availableSlots = [];

        $this->step = 2;
    }

    public function selectDate(string $date): void
    {
        $match = $this->days->first(
            fn ($day) =>
                $day['date']->toDateString() === $date
        );

        if (
            ! $match ||
            ! $match['open']
        ) {
            return;
        }

        $this->date = $date;

        $this->reset('time', 'error');

        /*
         * Bereken de beschikbare tijden meteen wanneer
         * de klant de datum kiest.
         */
        $this->availableSlots = $this->slotsFor(
            CarbonImmutable::parse($date)
        )->all();

        $this->step = 3;
    }

    public function selectTime(string $time): void
    {
        if (
            ! in_array(
                $time,
                $this->availableSlots,
                true
            )
        ) {
            return;
        }

        $this->time = $time;
        $this->error = null;
        $this->step = 4;
    }

    public function back(): void
    {
        $this->error = null;

        match ($this->step) {
            4 => $this->reset('time'),

            3 => [
                $this->reset(
                    'date',
                    'time'
                ),
                $this->availableSlots = [],
            ],

            2 => [
                $this->reset(
                    'serviceId',
                    'date',
                    'time'
                ),
                $this->availableSlots = [],
            ],

            default => null,
        };

        $this->step = max(
            1,
            $this->step - 1
        );
    }

    public function confirm(): mixed
    {
        /*
         * Controleer opnieuw op de server.
         *
         * We berekenen de actuele slots opnieuw zodat een
         * slot dat ondertussen door iemand anders geboekt is
         * niet alsnog gebruikt kan worden.
         */
        if (
            ! $this->selectedService ||
            ! $this->date ||
            ! $this->time
        ) {
            $this->error =
                'Deze tijd is niet meer beschikbaar. Kies een andere tijd.';

            $this->reset('time');

            $this->step = $this->date
                ? 3
                : 2;

            return null;
        }

        $currentSlots = $this->slotsFor(
            CarbonImmutable::parse($this->date)
        )->all();

        if (
            ! in_array(
                $this->time,
                $currentSlots,
                true
            )
        ) {
            $this->error =
                'Deze tijd is niet meer beschikbaar. Kies een andere tijd.';

            $this->reset('time');

            $this->availableSlots = $currentSlots;

            $this->step = 3;

            return null;
        }

        $startsAt = CarbonImmutable::parse(
            "{$this->date} {$this->time}",
            config('app.timezone')
        );

        try {
            app(AppointmentService::class)
                ->createAppointment(
                    shop: $this->barbershop,
                    customer: auth()->user(),
                    service: $this->selectedService,
                    startsAt: $startsAt,
                    actor: auth()->user(),
                );
        } catch (BookingException $e) {
            $this->error = $e->getMessage();

            $this->reset('time');

            $this->availableSlots = $this->slotsFor(
                CarbonImmutable::parse($this->date)
            )->all();

            $this->step = 3;

            return null;
        }

        session()->flash(
            'status',
            'Je afspraak is gemaakt.'
        );

        return $this->redirectRoute(
            'customer.dashboard',
            navigate: true
        );
    }

    public function render()
    {
        return view(
            'livewire.appointments.create-appointment'
        );
    }

    private function slotsFor(
        CarbonImmutable $day
    ): Collection {
        return app(
            AvailabilityService::class
        )
            ->getAvailableSlots(
                $this->barbershop,
                $this->selectedService,
                $day
            )
            ->map(
                fn ($slot) => $slot->format('H:i')
            )
            ->values();
    }
}
