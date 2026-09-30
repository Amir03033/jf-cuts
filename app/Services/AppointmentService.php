<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Exceptions\BookingException;
use App\Models\Appointment;
use App\Models\Barbershop;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    public function __construct(private AvailabilityService $availability)
    {
    }

    public function getAvailableSlots(Barbershop $shop, Service $service, CarbonInterface $date): Collection
    {
        return $this->availability->getAvailableSlots($shop, $service, $date);
    }

    /** Klant boekt voor zichzelf, of de kapper boekt voor een bestaande klant. */
    public function createAppointment(
        Barbershop $shop,
        User $customer,
        Service $service,
        CarbonInterface $startsAt,
        User $actor,
    ): Appointment {
        if (! $customer->isCustomer()) {
            throw new BookingException('Een afspraak kan alleen voor een klantaccount worden gemaakt.');
        }

        $actorMayBook = $actor->isBarber()
            ? $shop->owner_id === $actor->id
            : $actor->id === $customer->id;

        if (! $actorMayBook) {
            throw new AuthorizationException();
        }

        if ($service->barbershop_id !== $shop->id || ! $service->active) {
            throw new BookingException('Deze behandeling is niet beschikbaar.');
        }

        $start = CarbonImmutable::instance($startsAt)->startOfMinute();

        return DB::transaction(function () use ($shop, $customer, $service, $start) {
            $shop = $this->lockShop($shop);

            $this->assertSlotIsAvailable($shop, $service, $start);

            return $shop->appointments()->create([
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'starts_at' => $start,
                'ends_at' => $start->addMinutes($service->duration),
            ]);
        });
    }

    public function rescheduleAppointment(
        Appointment $appointment,
        CarbonInterface $newStartsAt,
        User $actor,
    ): Appointment {
        $start = CarbonImmutable::instance($newStartsAt)->startOfMinute();

        return DB::transaction(function () use ($appointment, $start, $actor) {
            $appointment = $this->lockForChange($appointment);

            $this->assertActorMayManage($appointment, $actor);
            $this->assertScheduled($appointment);

            if ($actor->isCustomer() && ! $appointment->canBeRescheduledByCustomer()) {
                throw new BookingException('Je kunt deze afspraak niet meer verplaatsen. Neem contact op met de kapper.');
            }

            $this->assertSlotIsAvailable($appointment->barbershop, $appointment->service, $start, $appointment);

            $appointment->starts_at = $start;
            $appointment->ends_at = $start->addMinutes($appointment->service->duration);
            $appointment->save();

            return $appointment;
        });
    }

    public function cancelAppointment(Appointment $appointment, User $actor): Appointment
    {
        return DB::transaction(function () use ($appointment, $actor) {
            $appointment = $this->lockForChange($appointment);

            $this->assertActorMayManage($appointment, $actor);
            $this->assertScheduled($appointment);

            if ($actor->isCustomer() && ! $appointment->canBeCancelledByCustomer()) {
                throw new BookingException('Je kunt deze afspraak niet meer annuleren. Neem contact op met de kapper.');
            }

            $appointment->status = AppointmentStatus::Cancelled;
            $appointment->save();

            return $appointment;
        });
    }

    /** Alleen de kapper. Het ontvangen bedrag bepaalt de omzet. */
    public function completeAppointment(Appointment $appointment, User $actor, int|float|string $amountPaid): Appointment
    {
        return DB::transaction(function () use ($appointment, $actor, $amountPaid) {
            $appointment = $this->lockForChange($appointment);

            $this->assertBarberOfShop($appointment, $actor);
            $this->assertScheduled($appointment);
            $this->assertHasStarted($appointment);

            if (! is_numeric($amountPaid) || $amountPaid < 0) {
                throw new BookingException('Vul een geldig ontvangen bedrag in.');
            }

            $appointment->status = AppointmentStatus::Completed;
            $appointment->amount_paid = $amountPaid;
            $appointment->completed_at = now();
            $appointment->save();

            return $appointment;
        });
    }

    /** Alleen de kapper. Een no-show levert geen omzet en geen bezoek op. */
    public function markAsNoShow(Appointment $appointment, User $actor): Appointment
    {
        return DB::transaction(function () use ($appointment, $actor) {
            $appointment = $this->lockForChange($appointment);

            $this->assertBarberOfShop($appointment, $actor);
            $this->assertScheduled($appointment);
            $this->assertHasStarted($appointment);

            $appointment->status = AppointmentStatus::NoShow;
            $appointment->save();

            return $appointment;
        });
    }

    // ---------- hulpmethodes ----------

    /**
     * Sluit de zaak even af voor andere boekingen tot de transactie klaar is.
     * Daardoor kunnen twee klanten op hetzelfde moment nooit hetzelfde tijdslot pakken.
     */
    private function lockShop(Barbershop $shop): Barbershop
    {
        return Barbershop::whereKey($shop->id)->lockForUpdate()->firstOrFail();
    }

    /** Altijd eerst de zaak, dan de afspraak locken (vaste volgorde voorkomt vastlopers). */
    private function lockForChange(Appointment $appointment): Appointment
    {
        $this->lockShop($appointment->barbershop);

        return Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
    }

    private function assertSlotIsAvailable(
        Barbershop $shop,
        Service $service,
        CarbonImmutable $start,
        ?Appointment $ignore = null,
    ): void {
        $slots = $this->availability->getAvailableSlots($shop, $service, $start, $ignore);

        if (! $slots->contains(fn ($slot) => $slot->equalTo($start))) {
            throw new BookingException('Dit tijdstip is niet (meer) beschikbaar.');
        }
    }

    private function assertScheduled(Appointment $appointment): void
    {
        if ($appointment->status !== AppointmentStatus::Scheduled) {
            throw new BookingException('Alleen geplande afspraken kunnen nog worden gewijzigd.');
        }
    }

    private function assertHasStarted(Appointment $appointment): void
    {
        if ($appointment->starts_at->isFuture()) {
            throw new BookingException('Deze afspraak is nog niet begonnen.');
        }
    }

    /** De kapper van deze zaak, of de klant van deze afspraak. */
    private function assertActorMayManage(Appointment $appointment, User $actor): void
    {
        $allowed = $actor->isBarber()
            ? $appointment->barbershop->owner_id === $actor->id
            : $appointment->customer_id === $actor->id;

        if (! $allowed) {
            throw new AuthorizationException();
        }
    }

    private function assertBarberOfShop(Appointment $appointment, User $actor): void
    {
        if (! $actor->isBarber() || $appointment->barbershop->owner_id !== $actor->id) {
            throw new AuthorizationException();
        }
    }
}
