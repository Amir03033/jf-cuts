<?php

namespace App\Livewire\Barber;

use App\Models\AvailabilityException;
use App\Models\AvailabilityRule;
use App\Models\Barbershop;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.customer')]
#[Title('Beschikbaarheid')]
class Availability extends Component
{
    public array $days = [];

    public array $exceptions = [];

    public ?string $exceptionDate = null;

    public ?string $exceptionStartTime = null;

    public ?string $exceptionEndTime = null;

    public bool $exceptionAvailable = false;

    public function mount(): void
    {
        $this->loadAvailability();
    }
    public function save(): void
    {
        $barbershop = $this->barbershop();

        foreach ($this->days as $dayOfWeek => $day) {
            AvailabilityRule::updateOrCreate(
                [
                    'barbershop_id' => $barbershop->id,
                    'day_of_week' => (int) $dayOfWeek,
                ],
                [
                    'barbershop_id' => $barbershop->id,

                    'start_time' => $day['available']
                        ? ($day['start_time'] ?: null)
                        : null,

                    'end_time' => $day['available']
                        ? ($day['end_time'] ?: null)
                        : null,

                    'is_available' => $day['available'],
                ]
            );
        }

        $this->loadAvailability();

        session()->flash(
            'status',
            'Beschikbaarheid opgeslagen.'
        );
    }

    public function addException(): void
    {
        $this->validate([
            'exceptionDate' => ['required', 'date'],
            'exceptionStartTime' => ['nullable', 'date_format:H:i'],
            'exceptionEndTime' => ['nullable', 'date_format:H:i'],
            'exceptionAvailable' => ['boolean'],
        ]);

        if (
            $this->exceptionAvailable &&
            (
                ! $this->exceptionStartTime ||
                ! $this->exceptionEndTime
            )
        ) {
            $this->addError(
                'exceptionStartTime',
                'Vul een begin- en eindtijd in.'
            );

            return;
        }

        if (
            $this->exceptionAvailable &&
            $this->exceptionStartTime >= $this->exceptionEndTime
        ) {
            $this->addError(
                'exceptionEndTime',
                'De eindtijd moet na de begintijd liggen.'
            );

            return;
        }

        $barbershop = $this->barbershop();

        AvailabilityException::updateOrCreate(
            [
                'barbershop_id' => $barbershop->id,
                'date' => $this->exceptionDate,
            ],
            [
                'start_time' => $this->exceptionAvailable
                    ? $this->exceptionStartTime
                    : null,

                'end_time' => $this->exceptionAvailable
                    ? $this->exceptionEndTime
                    : null,

                'is_available' => $this->exceptionAvailable,
            ]
        );

        $this->reset(
            'exceptionDate',
            'exceptionStartTime',
            'exceptionEndTime'
        );

        $this->exceptionAvailable = false;

        $this->loadAvailability();

        session()->flash(
            'status',
            'Uitzondering opgeslagen.'
        );
    }

    public function deleteException(int $id): void
    {
        $barbershop = $this->barbershop();

        AvailabilityException::query()
            ->where('id', $id)
            ->where('barbershop_id', $barbershop->id)
            ->delete();

        $this->loadAvailability();

        session()->flash(
            'status',
            'Uitzondering verwijderd.'
        );
    }

    private function barbershop(): Barbershop
    {
        return Barbershop::query()
            ->where('owner_id', auth()->id())
            ->firstOrFail();
    }

    private function loadAvailability(): void
    {
        $barbershop = $this->barbershop();

        $rules = $barbershop->availabilityRules()
            ->get()
            ->keyBy('day_of_week');

        $this->days = [];

        $names = [
            1 => 'Maandag',
            2 => 'Dinsdag',
            3 => 'Woensdag',
            4 => 'Donderdag',
            5 => 'Vrijdag',
            6 => 'Zaterdag',
            7 => 'Zondag',
        ];

        foreach ($names as $dayNumber => $name) {
            $rule = $rules->get($dayNumber);

            $this->days[$dayNumber] = [
                'name' => $name,

                'available' => $rule?->is_available ?? false,

                'start_time' => $rule?->start_time
                    ? substr($rule->start_time, 0, 5)
                    : '09:00',

                'end_time' => $rule?->end_time
                    ? substr($rule->end_time, 0, 5)
                    : '18:00',
            ];
        }

        $this->exceptions = $barbershop->availabilityExceptions()
            ->orderBy('date')
            ->get()
            ->map(function (AvailabilityException $exception) {
                return [
                    'id' => $exception->id,

                    'date' => $exception->date->format('Y-m-d'),

                    'start_time' => $exception->start_time
                        ? substr($exception->start_time, 0, 5)
                        : null,

                    'end_time' => $exception->end_time
                        ? substr($exception->end_time, 0, 5)
                        : null,

                    'is_available' => $exception->is_available,
                ];
            })
            ->toArray();
    }

    public function render()
    {
        return view('livewire.barber.availability');
    }
}
