<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Barbershop;
use Carbon\CarbonImmutable;

class RevenueService
{
    public function forPeriod(
        Barbershop $barbershop,
        CarbonImmutable $from,
        CarbonImmutable $until
    ): float {
        return (float) $barbershop->appointments()
            ->where('status', AppointmentStatus::Completed->value)
            ->where('starts_at', '>=', $from)
            ->where('starts_at', '<', $until)
            ->sum('amount_paid');
    }

    public function week(Barbershop $barbershop): float
    {
        $now = CarbonImmutable::now();

        return $this->forPeriod(
            $barbershop,
            $now->startOfWeek(),
            $now->startOfWeek()->addWeek()
        );
    }

    public function month(Barbershop $barbershop): float
    {
        $now = CarbonImmutable::now();

        return $this->forPeriod(
            $barbershop,
            $now->startOfMonth(),
            $now->startOfMonth()->addMonth()
        );
    }

    public function year(Barbershop $barbershop): float
    {
        $now = CarbonImmutable::now();

        return $this->forPeriod(
            $barbershop,
            $now->startOfYear(),
            $now->startOfYear()->addYear()
        );
    }
}
