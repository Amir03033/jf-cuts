<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/inloggen', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/inloggen', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/registreren', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/registreren', [AuthController::class, 'register'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/uitloggen', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', fn () => auth()->user()->isBarber()
        ? redirect()->route('barber.dashboard')
        : redirect()->route('customer.dashboard'))->name('dashboard');

    // Alleen klanten
    Route::middleware('role:customer')->prefix('customer')->name('customer.')->group(function () {
        Route::view('/dashboard', 'customer.dashboard')->name('dashboard');
    });

    // Alleen de kapper
    Route::middleware('role:barber')->prefix('barber')->name('barber.')->group(function () {
        Route::view('/dashboard', 'barber.dashboard')->name('dashboard');
    });
});
