@extends('layouts.barber')

@section('title', 'Dashboard')

@section('content')


    <h1>Dashboard 👋</h1>

    {{-- Omzet --}}
    <p class="label">Omzet</p>

    <livewire:barber.revenue-card />

    {{-- Aankomende afspraken --}}
    <p class="label spaced">Aankomende afspraken</p>

    @forelse ($upcomingAppointments ?? collect() as $appointment)

        <div class="card">

            <div class="when">
                {{ ucfirst(
                    $appointment->starts_at
                        ->locale('nl')
                        ->translatedFormat('l j F')
                ) }}
            </div>

            <div class="time">
                {{ $appointment->starts_at->format('H:i') }}
            </div>

            <div class="service">

            <span>
                {{ $appointment->customer?->name ?? 'Onbekende klant' }}
            </span>

                <span>
                {{ $appointment->service?->name ?? 'Onbekende dienst' }}
            </span>

            </div>

            @if ($appointment->service)
                <p class="muted">
                    €{{ number_format(
                    $appointment->service->price,
                    2,
                    ',',
                    '.'
                ) }}
                </p>
            @endif

        </div>

    @empty

        <div class="card">
            <p class="muted">
                Geen aankomende afspraken.
            </p>
        </div>

    @endforelse

    {{-- Vandaag --}}
    <p class="label spaced">Vandaag</p>

    <div class="card">

        @php
            $todayCount = $todayAppointments?->count() ?? 0;
        @endphp

        <div class="big">
            {{ $todayCount }}
        </div>

        <p class="muted">
            {{ $todayCount === 1 ? 'afspraak' : 'afspraken' }}
            vandaag
        </p>

    </div>

    {{-- Snelle acties --}}
    <p class="label spaced">Snelle acties</p>

    <div class="card">

        <a
            class="btn btn-primary"
            href="{{ route('barber.appointments.new') }}"
        >
            + Nieuwe afspraak
        </a>

        <a
            class="btn btn-outline"
            href="{{ route('barber.appointments') }}"
        >
            Afspraken bekijken
        </a>

        <a
            class="btn btn-outline"
            href="{{ route('barber.customers') }}"
        >
            Klanten
        </a>

        <a
            class="btn btn-outline"
            href="{{ route('barber.services') }}"
        >
            Diensten
        </a>

        <a
            class="btn btn-outline"
            href="{{ route('barber.availability') }}"
        >
            Beschikbaarheid
        </a>

    </div>


@endsection
