<?php

namespace App\Livewire\Barber;

use App\Exceptions\BookingException;
use App\Models\Barbershop;
use App\Models\Service;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.customer')]
#[Title('Nieuwe afspraak')]
class CreateAppointment extends Component
{
    public int $step = 1;

    public ?int $customerId = null;
    public ?int $serviceId = null;
    public ?string $date = null;
    public ?string $time = null;

    public string $search = '';

    public ?string $error = null;

    public array $availableSlots = [];

    #[\Livewire\Attributes\Computed]
    public function barbershop(): Barbershop
    {
        return Barbershop::query()
            ->where('owner_id', auth()->id())
            ->firstOrFail();
    }

    #[\Livewire\Attributes\Computed]
    public function customers(): Collection
    {
        return User::query()
            ->where('role', 'customer')
            ->when(
                trim($this->search) !== '',
                function ($query) {
                    $search = trim($this->search);

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                }
            )
            ->orderBy('name')
            ->limit(20)
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function selectedCustomer(): ?User
    {
        if (! $this->customerId) {
            return null;
        }

        return User::query()
            ->where('id', $this->customerId)
            ->where('role', 'customer')
            ->first();
    }

    #[\Livewire\Attributes\Computed]
    public function services(): Collection
    {
        return $this->barbershop
            ->services()
            ->where('active', true)
            ->orderBy('name')
            ->get();
    }

    #[\Livewire\Attributes\Computed]
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

    #[\Livewire\Attributes\Computed]
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

    public function selectCustomer(int $id): void
    {
        $customer = User::query()
            ->whereKey($id)
            ->where('role', 'customer')
            ->first();

        if (! $customer) {
            abort(404);
        }

        $this->customerId = $customer->id;
        $this->error = null;
        $this->step = 2;
    }

    public function selectService(int $id): void
    {
        abort_unless(
            $this->services->contains('id', $id),
            422
        );

        $this->serviceId = $id;
        $this->date = null;
        $this->time = null;
        $this->availableSlots = [];
        $this->error = null;

        $this->step = 3;
    }

    public function selectDate(string $date): void
    {
        $match = $this->days->first(
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

        $this->step = 4;
    }

    public function selectTime(string $time): void
    {
        if (! in_array($time, $this->availableSlots, true)) {
            return;
        }

        $this->time = $time;
        $this->error = null;
        $this->step = 5;
    }

    public function back(): void
    {
        $this->error = null;

        match ($this->step) {
            5 => $this->time = null,

            4 => [
                $this->date = null,
                $this->time = null,
                $this->availableSlots = [],
            ],

            3 => [
                $this->serviceId = null,
                $this->date = null,
                $this->time = null,
                $this->availableSlots = [],
            ],

            2 => [
                $this->customerId = null,
                $this->serviceId = null,
                $this->date = null,
                $this->time = null,
                $this->availableSlots = [],
            ],

            default => null,
        };

        $this->step = max(1, $this->step - 1);
    }

    public function confirm(): mixed
    {
        if (
            ! $this->selectedCustomer ||
            ! $this->selectedService ||
            ! $this->date ||
            ! $this->time
        ) {
            $this->error =
                'Niet alle gegevens zijn ingevuld.';

            return null;
        }

        $currentSlots = $this->slotsFor(
            CarbonImmutable::parse($this->date)
        )->all();

        if (! in_array($this->time, $currentSlots, true)) {
            $this->error =
                'Deze tijd is niet meer beschikbaar.';

            $this->availableSlots = $currentSlots;
            $this->time = null;
            $this->step = 4;

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
                    customer: $this->selectedCustomer,
                    service: $this->selectedService,
                    startsAt: $startsAt,
                    actor: auth()->user(),
                );
        } catch (BookingException $e) {
            $this->error = $e->getMessage();

            $this->availableSlots = $this->slotsFor(
                CarbonImmutable::parse($this->date)
            )->all();

            $this->time = null;
            $this->step = 4;

            return null;
        }

        session()->flash(
            'status',
            'De afspraak is gemaakt.'
        );

        return $this->redirectRoute(
            'barber.appointments',
            navigate: true
        );
    }

    private function slotsFor(
        CarbonImmutable $day
    ): Collection {
        return app(AvailabilityService::class)
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

    public function render()
    {
        return view(
            'livewire.barber.create-appointment'
        );
    }
}
