<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarberAppointmentsController;
use App\Http\Controllers\BarberCustomersController;
use App\Http\Controllers\BarberDashboardController;
use App\Http\Controllers\BarberServiceController;
use App\Http\Controllers\BarberSettingsController;
use App\Http\Controllers\BarberShopImageController;
use App\Http\Controllers\CustomerAppointmentsController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\CustomerProfileController;
use App\Livewire\Appointments\CreateAppointment;
use App\Livewire\Appointments\RescheduleAppointment as CustomerRescheduleAppointment;
use App\Livewire\Barber\Availability;
use App\Livewire\Barber\CreateAppointment as BarberCreateAppointment;
use App\Livewire\Barber\RescheduleAppointment as BarberRescheduleAppointment;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::redirect('/', '/dashboard');


/*
|--------------------------------------------------------------------------
| Guest
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/inloggen', [
        AuthController::class,
        'showLogin',
    ])->name('login');

    Route::post('/inloggen', [
        AuthController::class,
        'login',
    ])->middleware('throttle:5,1');

    Route::get('/registreren', [
        AuthController::class,
        'showRegister',
    ])->name('register');

    Route::post('/registreren', [
        AuthController::class,
        'register',
    ])->middleware('throttle:10,1');
});


/*
|--------------------------------------------------------------------------
| Authenticated
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user->isBarber()) {
            return redirect()->route('barber.dashboard');
        }

        return redirect()->route('customer.dashboard');
    })->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    Route::post('/uitloggen', [
        AuthController::class,
        'logout',
    ])->name('logout');


    /*
    |--------------------------------------------------------------------------
    | Barber
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:barber')
        ->prefix('barber')
        ->name('barber.')
        ->group(function () {

            // Dashboard
            Route::get('/dashboard', [
                BarberDashboardController::class,
                'index',
            ])->name('dashboard');

            // Availability
            Route::get('/availability', Availability::class)
                ->name('availability');

            // Appointments
            Route::get('/appointments', [
                BarberAppointmentsController::class,
                'index',
            ])->name('appointments');

            Route::get(
                '/appointments/new',
                BarberCreateAppointment::class
            )->name('appointments.new');

            Route::get(
                '/appointments/{appointment}/edit',
                BarberRescheduleAppointment::class
            )->name('appointments.edit');

            Route::post('/appointments/{appointment}/cancel', [
                BarberAppointmentsController::class,
                'cancel',
            ])->name('appointments.cancel');

            Route::post('/appointments/{appointment}/complete', [
                BarberAppointmentsController::class,
                'complete',
            ])->name('appointments.complete');

            Route::post('/appointments/{appointment}/no-show', [
                BarberAppointmentsController::class,
                'noShow',
            ])->name('appointments.no-show');

            // Customers
            Route::get('/customers', [
                BarberCustomersController::class,
                'index',
            ])->name('customers');

            Route::get('/customers/{customer}', [
                BarberCustomersController::class,
                'show',
            ])->name('customers.show');

            // Services
            Route::get('/services', [
                BarberServiceController::class,
                'index',
            ])->name('services');

            Route::get('/services/new', [
                BarberServiceController::class,
                'create',
            ])->name('services.new');

            Route::post('/services', [
                BarberServiceController::class,
                'store',
            ])->name('services.store');

            Route::get('/services/{service}/edit', [
                BarberServiceController::class,
                'edit',
            ])->name('services.edit');

            Route::put('/services/{service}', [
                BarberServiceController::class,
                'update',
            ])->name('services.update');

            // Settings
            Route::get('/settings', [
                BarberSettingsController::class,
                'edit',
            ])->name('settings');

            Route::put('/settings', [
                BarberSettingsController::class,
                'update',
            ])->name('settings.update');

            // Photos
            Route::get('/photos', [
                BarberShopImageController::class,
                'index',
            ])->name('photos');

            Route::post('/photos', [
                BarberShopImageController::class,
                'store',
            ])->name('photos.store');

            Route::delete('/photos/{image}', [
                BarberShopImageController::class,
                'destroy',
            ])->name('photos.destroy');
        });


    /*
    |--------------------------------------------------------------------------
    | Customer
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:customer')
        ->prefix('customer')
        ->name('customer.')
        ->group(function () {

            // Dashboard
            Route::get('/dashboard', [
                CustomerDashboardController::class,
                'index',
            ])->name('dashboard');

            // Appointments
            Route::get('/appointments', [
                CustomerAppointmentsController::class,
                'index',
            ])->name('appointments');

            Route::get(
                '/appointments/new',
                CreateAppointment::class
            )->name('appointments.new');

            Route::get(
                '/appointments/{appointment}/edit',
                CustomerRescheduleAppointment::class
            )->name('appointments.edit');

            Route::post('/appointments/{appointment}/cancel', [
                CustomerAppointmentsController::class,
                'cancel',
            ])->name('appointments.cancel');

            // Profile
            Route::get('/profile', [
                CustomerProfileController::class,
                'edit',
            ])->name('profile');

            Route::put('/profile', [
                CustomerProfileController::class,
                'update',
            ])->name('profile.update');

            Route::put('/profile/password', [
                CustomerProfileController::class,
                'updatePassword',
            ])->name('profile.password');
        });
});

