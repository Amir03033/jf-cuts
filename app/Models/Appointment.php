<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Appointment extends Model
{
    use HasFactory;

    // status, amount_paid, completed_at en barbershop_id staan bewust NIET in $fillable:
    // die worden alleen via de AppointmentService gezet.
    protected $fillable = ['customer_id', 'service_id', 'starts_at', 'ends_at'];

    protected $attributes = [
        'status' => 'scheduled',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'completed_at' => 'datetime',
            'amount_paid' => 'decimal:2',
            'status' => AppointmentStatus::class,
        ];
    }

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
    // Alleen 'completed' telt als bezoek en levert omzet op
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', AppointmentStatus::Completed);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', AppointmentStatus::Scheduled);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->scheduled()->where('starts_at', '>=', now());
    }

// Mag een klant deze afspraak nog verplaatsen? (tot 1 uur voor aanvang)
    public function canBeRescheduledByCustomer(): bool
    {
        return $this->status === AppointmentStatus::Scheduled
            && now()->lte($this->starts_at->copy()->subMinutes(
                $this->barbershop->settings->reschedule_limit
            ));
    }

// Mag een klant deze afspraak nog annuleren? (tot 1 uur voor aanvang)
    public function canBeCancelledByCustomer(): bool
    {
        return $this->status === AppointmentStatus::Scheduled
            && now()->lte($this->starts_at->copy()->subMinutes(
                $this->barbershop->settings->cancellation_limit
            ));
    }


}
