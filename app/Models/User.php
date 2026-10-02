<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Velden die massaal ingevuld mogen worden.
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    /**
     * Velden die verborgen blijven in arrays en JSON.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Database casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Controleert of de gebruiker een barber is.
     */
    public function isBarber(): bool
    {
        return $this->role === UserRole::Barber;
    }

    /**
     * Controleert of de gebruiker een customer is.
     */
    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    /**
     * Barbershops die door deze gebruiker worden beheerd.
     */
    public function barbershops(): HasMany
    {
        return $this->hasMany(
            Barbershop::class,
            'owner_id'
        );
    }

    /**
     * Afspraken van deze customer.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(
            Appointment::class,
            'customer_id'
        );
    }
}
