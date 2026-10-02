<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Barbershop;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Geeft de openingstijden voor een bepaalde datum.
     *
     * Een uitzondering heeft voorrang op het normale weekrooster.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}|null
     */
    public function getOpeningHours(
        Barbershop $shop,
        CarbonInterface $date
    ): ?array {
        $day = $date->toDateString();

        $exception = $shop->availabilityExceptions()
            ->whereDate('date', $day)
            ->first();

        $source = $exception
            ?? $shop->availabilityRules()
                ->where('day_of_week', $date->dayOfWeekIso)
                ->first();

        if (
            ! $source ||
            ! $source->is_available ||
            ! $source->start_time ||
            ! $source->end_time
        ) {
            return null;
        }

        return [
            CarbonImmutable::parse(
                "{$day} {$source->start_time}",
                config('app.timezone')
            ),

            CarbonImmutable::parse(
                "{$day} {$source->end_time}",
                config('app.timezone')
            ),
        ];
    }

    /**
     * Geeft alle beschikbare starttijden voor een dienst op een datum.
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

        $maxBookingDays = (int) (
            $settings->max_booking_days ?? 30
        );

        $interval = max(
            1,
            (int) ($settings->booking_interval ?? 30)
        );

        $duration = (int) $service->duration;

        if ($duration <= 0) {
            return collect();
        }

        $day = CarbonImmutable::instance($date)
            ->startOfDay();

        $today = CarbonImmutable::now()
            ->startOfDay();

        /*
         * Geen afspraken in het verleden.
         */
        if ($day->lt($today)) {
            return collect();
        }

        /*
         * Niet verder vooruit boeken dan toegestaan.
         */
        if (
            $day->gt(
                $today->addDays($maxBookingDays)
            )
        ) {
            return collect();
        }

        $hours = $this->getOpeningHours($shop, $day);

        if (! $hours) {
            return collect();
        }

        [$open, $close] = $hours;

        /*
         * Alle bestaande afspraken die tijd blokkeren.
         */
        $busy = $shop->appointments()
            ->whereNotIn('status', [
                AppointmentStatus::Cancelled->value,
                AppointmentStatus::NoShow->value,
            ])
            ->where('starts_at', '<', $close)
            ->where('ends_at', '>', $open)
            ->when(
                $ignore,
                fn ($query) => $query->whereKeyNot($ignore->id)
            )
            ->get([
                'starts_at',
                'ends_at',
            ]);

        $slots = collect();

        $start = $open;

        while (
        $start
            ->addMinutes($duration)
            ->lte($close)
        ) {
            $end = $start->addMinutes($duration);

            /*
             * Controleer of deze tijd overlapt
             * met een bestaande afspraak.
             */
            $overlaps = $busy->contains(
                fn ($appointment) =>
                    $start->lt($appointment->ends_at)
                    &&
                    $end->gt($appointment->starts_at)
            );

            /*
             * Voor vandaag mag alleen een tijdstip
             * in de toekomst worden geboekt.
             */
            $isFuture = $day->isToday()
                ? $start->gt(CarbonImmutable::now())
                : true;

            if ($isFuture && ! $overlaps) {
                $slots->push($start);
            }

            $start = $start->addMinutes($interval);
        }

        return $slots->values();
    }
}
