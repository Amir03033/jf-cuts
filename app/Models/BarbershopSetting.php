<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarbershopSetting extends Model
{
    protected $fillable = [
        'booking_interval',
        'max_booking_days',
        'cancellation_limit',
        'reschedule_limit',
    ];

    // Standaardwaarden, ook als de rij nog niet in de database staat
    protected $attributes = [
        'booking_interval' => 30,
        'max_booking_days' => 30,
        'cancellation_limit' => 60,
        'reschedule_limit' => 60,
    ];

    protected function casts(): array
    {
        return [
            'booking_interval' => 'integer',
            'max_booking_days' => 'integer',
            'cancellation_limit' => 'integer',
            'reschedule_limit' => 'integer',
        ];
    }

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class);
    }
}
