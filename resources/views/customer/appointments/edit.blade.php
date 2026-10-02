@extends('layouts.customer')

@section('title', 'Afspraak verplaatsen')

@section('content')

    <a href="{{ route('customer.appointments') }}" class="muted">
        ← Terug naar mijn afspraken
    </a>

    <h1>Afspraak verplaatsen</h1>

    <p class="muted">
        Kies een nieuwe datum en tijd voor je afspraak.
    </p>

    <div class="card">

        <div class="when">
            Huidige afspraak
        </div>

        <div class="time">
            {{ $appointment->starts_at->format('H:i') }}
        </div>

        <div class="service">
            <span>
                {{ $appointment->service->name }}
            </span>

            <span>
                {{ $appointment->starts_at
                    ->locale('nl')
                    ->translatedFormat('j F Y') }}
            </span>
        </div>

    </div>

    <form
        method="POST"
        action="{{ route('customer.appointments.update', $appointment) }}"
    >
        @csrf
        @method('PUT')

        <div class="form-group">

            <label for="date">
                Nieuwe datum
            </label>

            <input
                id="date"
                type="date"
                name="date"
                value="{{ old('date') }}"
                min="{{ now()->format('Y-m-d') }}"
                max="{{ now()->addDays(
                    $appointment->barbershop->settings->max_booking_days ?? 30
                )->format('Y-m-d') }}"
                required
            >

            @error('date')
            <small class="error">
                {{ $message }}
            </small>
            @enderror

        </div>

        <div class="form-group">

            <label for="time">
                Nieuwe tijd
            </label>

            <input
                id="time"
                type="time"
                name="time"
                value="{{ old('time') }}"
                required
            >

            @error('time')
            <small class="error">
                {{ $message }}
            </small>
            @enderror

        </div>

        @if (session('error'))
            <div class="card">
                <p class="error">
                    {{ session('error') }}
                </p>
            </div>
        @endif

        <button
            type="submit"
            class="btn btn-primary"
        >
            Afspraak verplaatsen
        </button>

    </form>

@endsection
