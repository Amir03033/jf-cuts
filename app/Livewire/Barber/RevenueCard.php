<?php

namespace App\Livewire\Barber;

use App\Models\Barbershop;
use App\Services\RevenueService;
use Livewire\Component;

class RevenueCard extends Component
{
    public string $period = 'week';

    public function setPeriod(string $period): void
    {
        abort_unless(
            in_array($period, ['week', 'month', 'year'], true),
            422
        );

        $this->period = $period;
    }

    public function render()
    {
        $barbershop = Barbershop::query()
            ->where('owner_id', auth()->id())
            ->first();

        if (! $barbershop) {
            return view('livewire.barber.revenue-card', [
                'revenue' => 0,
            ]);
        }

        $revenueService = app(RevenueService::class);

        $revenue = match ($this->period) {
            'month' => $revenueService->month($barbershop),
            'year' => $revenueService->year($barbershop),
            default => $revenueService->week($barbershop),
        };

        return view('livewire.barber.revenue-card', [
            'revenue' => $revenue,
        ]);
    }
}

