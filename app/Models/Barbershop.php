<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Barbershop extends Model
{
    use HasFactory;

    // owner_id staat er bewust NIET in: de eigenaar zet je via de relatie, nooit via een formulier
    protected $fillable = ['name', 'description', 'address'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function availabilityRules(): HasMany
    {
        return $this->hasMany(AvailabilityRule::class);
    }

    public function availabilityExceptions(): HasMany
    {
        return $this->hasMany(AvailabilityException::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ShopImage::class)->orderBy('sort_order');
    }
    public function settings(): HasOne
    {
        return $this->hasOne(BarbershopSetting::class)->withDefault();
    }

    public function shopImages(): HasMany
    {
        return $this->hasMany(ShopImage::class);
    }
}
