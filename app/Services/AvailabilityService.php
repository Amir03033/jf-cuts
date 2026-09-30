<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Barbershop;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Openingstijden voor één datum: uitzondering gaat voor weekrooster.
     * Geeft [opening, sluiting] of null als de zaak gesloten is.
     */
    public function getOpeningHours(Barbershop $shop, CarbonInterface $date): ?array
    {
        $day = $date->toDateString();

        $exception = $shop->availabilityExceptions()->whereDate('date', $day)->first();

        $source = $exception ?? $shop->availabilityRules()
            ->where('day_of_week', $date->dayOfWeekIso)   // 1 = maandag ... 7 = zondag
            ->first();

        if (! $source || ! $source->is_available || ! $source->start_time || ! $source->end_time) {
            return null;   // gesloten (of onvolledig ingevuld: dan liever gesloten)
        }

        return [
            Carbon::parse("$day {$source->start_time}"),
            Carbon::parse("$day {$source->end_time}"),
        ];
    }

    /**
     * Alle starttijden die op deze datum nog te boeken zijn voor deze dienst.
     * $ignore = een afspraak die we willen verplaatsen (die blokkeert zichzelf niet).
     *
     * @return Collection<int, CarbonImmutable>
     */
    public function getAvailableSlots(
        Barbershop $shop,
        Service $service,
        CarbonInterface $date,
        ?Appointment $ignore = null,
    ): Collection {
        $settings = $shop->settings;
        $day = Carbon::instance($date)->startOfDay();
        $today = now()->startOfDay();

        // Niet in het verleden en maximaal max_booking_days vooruit
        if ($day->lt($today) || $day->gt($today->copy()->addDays($settings->max_booking_days))) {
            return collect();
        }

        $hours = $this->getOpeningHours($shop, $day);

        if (! $hours) {
            return collect();
        }

        [$open, $close] = $hours;
        $duration = $service->duration;
        $interval = max(1, $settings->booking_interval);
        $now = now();

        // Bestaande afspraken die tijd innemen (geannuleerd en no-show geven de tijd vrij)
        $busy = $shop->appointments()
            ->whereNotIn('status', [
                AppointmentStatus::Cancelled->value,
                AppointmentStatus::NoShow->value,
            ])
            ->where('starts_at', '<', $close)
            ->where('ends_at', '>', $open)
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->get(['starts_at', 'ends_at']);

        $slots = collect();
        $start = CarbonImmutable::instance($open);

        // De behandeling moet volledig binnen de openingstijd passen
        while ($start->addMinutes($duration)->lte($close)) {
            $end = $start->addMinutes($duration);

            $overlaps = $busy->contains(
                fn ($appointment) => $start->lt($appointment->ends_at) && $end->gt($appointment->starts_at)
            );

            if ($start->gt($now) && ! $overlaps) {
                $slots->push($start);
            }

            $start = $start->addMinutes($interval);
        }

        return $slots;
    }
}
