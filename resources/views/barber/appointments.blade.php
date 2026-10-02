@extends('layouts.barber')

@section('title', 'Afspraken')

@section('content')


<a href="{{ route('barber.dashboard') }}" class="muted">
    ← Terug naar dashboard
</a>

<h1>Afspraken</h1>

<p class="muted">
    Beheer hier alle afspraken van {{ $barbershop->name }}.
</p>

{{-- Meldingen --}}
@if (session('status'))
    <div class="card">
        <p>
            {{ session('status') }}
        </p>
    </div>
@endif

@if (session('error'))
    <div class="card">
        <p class="error">
            {{ session('error') }}
        </p>
    </div>
@endif

@php
    $scheduled = \App\Enums\AppointmentStatus::Scheduled;
    $completed = \App\Enums\AppointmentStatus::Completed;
    $cancelled = \App\Enums\AppointmentStatus::Cancelled;
    $noShow = \App\Enums\AppointmentStatus::NoShow;

    $upcoming = $appointments->filter(
        fn ($appointment) =>
            $appointment->status === $scheduled
            && $appointment->starts_at->isFuture()
    );

    $history = $appointments->reject(
        fn ($appointment) =>
            $appointment->status === $scheduled
            && $appointment->starts_at->isFuture()
    );
@endphp

{{-- Komende afspraken --}}
<p class="label spaced">
    Komende afspraken
</p>

@forelse ($upcoming as $appointment)

    <div class="card">

        <div class="when">
            {{ ucfirst(
                $appointment->starts_at
                    ->locale('nl')
                    ->translatedFormat('l j F Y')
            ) }}
        </div>

        <div class="time">
            {{ $appointment->starts_at->format('H:i') }}
        </div>

        <div class="service">
            <span>
                {{ $appointment->service->name }}
            </span>

            <span>
                €{{ number_format(
                    $appointment->service->price,
                    2,
                    ',',
                    '.'
                ) }}
            </span>
        </div>

        <p>
            <strong>Klant:</strong>
            {{ $appointment->customer->name }}
        </p>

        {{-- Verplaatsen --}}
        <a
            href="{{ route(
                'barber.appointments.edit',
                $appointment
            ) }}"
            class="btn btn-outline"
            style="margin-top: 12px;"
        >
            Afspraak verplaatsen
        </a>

        {{-- Annuleren --}}
        <form
            method="POST"
            action="{{ route(
                'barber.appointments.cancel',
                $appointment
            ) }}"
            onsubmit="return confirm(
                'Weet je zeker dat je deze afspraak wilt annuleren?'
            );"
            style="margin-top: 8px;"
        >
            @csrf

            <button
                type="submit"
                class="btn btn-outline"
            >
                Afspraak annuleren
            </button>
        </form>

    </div>

@empty

    <div class="card">
        <p class="muted">
            Er zijn geen komende afspraken.
        </p>
    </div>

@endforelse


{{-- Geschiedenis / afgeronde afspraken --}}
<p class="label spaced">
    Geschiedenis
</p>

@forelse ($history as $appointment)

    <div class="card">

        <div class="when">
            {{ ucfirst(
                $appointment->starts_at
                    ->locale('nl')
                    ->translatedFormat('l j F Y')
            ) }}
        </div>

        <div class="time">
            {{ $appointment->starts_at->format('H:i') }}
        </div>

        <div class="service">

            <span>
                {{ $appointment->service->name }}
            </span>

            <span>
                €{{ number_format(
                    $appointment->service->price,
                    2,
                    ',',
                    '.'
                ) }}
            </span>

        </div>

        <p>
            <strong>Klant:</strong>
            {{ $appointment->customer->name }}
        </p>

        {{-- Afspraak die nu gestart is / voorbij is --}}
        @if ($appointment->status === $scheduled && ! $appointment->starts_at->isFuture())

            <form
                method="POST"
                action="{{ route(
                    'barber.appointments.complete',
                    $appointment
                ) }}"
                style="margin-top: 12px;"
            >
                @csrf

                <label for="amount_paid_{{ $appointment->id }}">
                    Ontvangen bedrag
                </label>

                <input
                    id="amount_paid_{{ $appointment->id }}"
                    type="number"
                    name="amount_paid"
                    min="0"
                    step="0.01"
                    value="{{ old(
                        'amount_paid',
                        $appointment->service->price
                    ) }}"
                    required
                >

                <button
                    type="submit"
                    class="btn btn-primary"
                    style="margin-top: 8px;"
                >
                    Afspraak voltooien
                </button>
            </form>

            <form
                method="POST"
                action="{{ route(
                    'barber.appointments.no-show',
                    $appointment
                ) }}"
                onsubmit="return confirm(
                    'Deze klant is niet verschenen?'
                );"
                style="margin-top: 8px;"
            >
                @csrf

                <button
                    type="submit"
                    class="btn btn-outline"
                >
                    No-show
                </button>
            </form>

        @else

            {{-- Status --}}
            <p class="muted">

                @switch($appointment->status)

                    @case($completed)
                        Voltooid
                        @break

                    @case($cancelled)
                        Geannuleerd
                        @break

                    @case($noShow)
                        No-show
                        @break

                    @default
                        {{ ucfirst($appointment->status->value) }}

                @endswitch

            </p>

            {{-- Werkelijk ontvangen bedrag --}}
            @if ($appointment->status === $completed)

                <p>
                    Ontvangen:
                    <strong>
                        €{{ number_format(
                            $appointment->amount_paid,
                            2,
                            ',',
                            '.'
                        ) }}
                    </strong>
                </p>

            @endif

        @endif

    </div>

@empty

    <div class="card">
        <p class="muted">
            Nog geen afspraakgeschiedenis.
        </p>
    </div>

@endforelse


@endsection
