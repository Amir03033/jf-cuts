@extends('layouts.barber')

@section('title', 'Klant')

@section('content')

    <a href="{{ route('barber.customers') }}" class="muted">
        ← Terug naar klanten
    </a>

    <h1>{{ $customer->name }}</h1>

    <p class="muted">
        Klantgegevens
    </p>

    <div class="card">

        <p>
            <strong>E-mail:</strong>
            {{ $customer->email }}
        </p>

        @if ($customer->phone)
            <p>
                <strong>Telefoon:</strong>
                {{ $customer->phone }}
            </p>
        @endif

    </div>

    <p class="label spaced">
        Overzicht
    </p>

    <div class="card">

        <div class="big">
            {{ $visits }}
        </div>

        <p class="muted">
            {{ $visits === 1 ? 'voltooid bezoek' : 'voltooide bezoeken' }}
        </p>

    </div>

    <div class="card">

        <div class="big">
            €{{ number_format(
                $totalReceived,
                2,
                ',',
                '.'
            ) }}
        </div>

        <p class="muted">
            Totaal ontvangen
        </p>

    </div>

    <a
        href="{{ route('barber.appointments.new') }}"
        class="btn btn-primary"
    >
        + Nieuwe afspraak
    </a>

    <p class="label spaced">
        Afspraken
    </p>

    @forelse ($appointments as $appointment)

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

            <p class="muted">

                @switch($appointment->status)

                    @case(\App\Enums\AppointmentStatus::Scheduled)
                        Gepland
                        @break

                    @case(\App\Enums\AppointmentStatus::Completed)
                        Voltooid
                        @break

                    @case(\App\Enums\AppointmentStatus::Cancelled)
                        Geannuleerd
                        @break

                    @case(\App\Enums\AppointmentStatus::NoShow)
                        No-show
                        @break

                    @default
                        {{ ucfirst($appointment->status->value) }}

                @endswitch

            </p>

            @if ($appointment->status === \App\Enums\AppointmentStatus::Completed)

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

        </div>

    @empty

        <div class="card">
            <p class="muted">
                Deze klant heeft nog geen afspraken.
            </p>
        </div>

    @endforelse

@endsection
