@extends('layouts.customer')

@section('title', 'Afspraken')

@section('content')

    <section class="page-header">

        <p class="eyebrow">
            JF CUTS
        </p>

        <h1>
            Mijn afspraken
        </h1>

        <p class="muted">
            Bekijk je komende afspraken en geschiedenis.
        </p>

    </section>


    @php
        $upcoming = $appointments
            ->filter(fn ($appointment) =>
                $appointment->status === \App\Enums\AppointmentStatus::Scheduled
                && $appointment->starts_at->isFuture()
            );

        $history = $appointments
            ->filter(fn ($appointment) =>
                ! (
                    $appointment->status === \App\Enums\AppointmentStatus::Scheduled
                    && $appointment->starts_at->isFuture()
                )
            );
    @endphp


    <section class="section">

        <div class="section-heading">
            <p class="label">
                Komend
            </p>

            <span class="count-badge">
                {{ $upcoming->count() }}
            </span>
        </div>


        @forelse ($upcoming as $appointment)

            <article class="appointment-list-card">

                <div class="list-date">

                    <span>
                        {{ strtoupper($appointment->starts_at->locale('nl')->translatedFormat('D')) }}
                    </span>

                    <strong>
                        {{ $appointment->starts_at->format('d') }}
                    </strong>

                </div>


                <div class="list-main">

                    <div class="list-time">
                        {{ $appointment->starts_at->format('H:i') }}
                    </div>

                    <strong>
                        {{ $appointment->service->name }}
                    </strong>

                    <span class="muted">
                        €{{ number_format($appointment->service->price, 0, ',', '.') }}
                    </span>

                </div>


                <span class="status status-scheduled">
                    Gepland
                </span>

            </article>


            <div class="appointment-actions">

                @if ($appointment->canBeRescheduledByCustomer())

                    <a
                        href="{{ route('customer.appointments.edit', $appointment) }}"
                        class="btn btn-outline"
                    >
                        Verplaatsen
                    </a>

                @endif


                @if ($appointment->canBeCancelledByCustomer())

                    <form
                        method="POST"
                        action="{{ route('customer.appointments.cancel', $appointment) }}"
                        onsubmit="return confirm('Weet je zeker dat je deze afspraak wilt annuleren?');"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="btn btn-danger-outline"
                        >
                            Annuleren
                        </button>
                    </form>

                @else

                    <p class="restriction-text">
                        Binnen 1 uur voor de afspraak kun je deze niet meer zelf annuleren.
                    </p>

                @endif

            </div>

        @empty

            <div class="empty-card">

                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="5" width="18" height="16" rx="3"/>
                        <path d="M16 3v4M8 3v4M3 10h18"/>
                    </svg>
                </div>

                <h2>
                    Geen komende afspraken
                </h2>

                <p class="muted">
                    Tijd voor een nieuwe afspraak?
                </p>

                <a
                    href="{{ route('customer.appointments.new') }}"
                    class="btn btn-primary"
                >
                    Nieuwe afspraak
                </a>

            </div>

        @endforelse

    </section>


    <section class="section history-section">

        <p class="label">
            Geschiedenis
        </p>


        @forelse ($history as $appointment)

            @php
                $status = match ($appointment->status) {
                    \App\Enums\AppointmentStatus::Completed => [
                        'label' => 'Voltooid',
                        'class' => 'status-completed',
                    ],

                    \App\Enums\AppointmentStatus::Cancelled => [
                        'label' => 'Geannuleerd',
                        'class' => 'status-cancelled',
                    ],

                    \App\Enums\AppointmentStatus::NoShow => [
                        'label' => 'Niet verschenen',
                        'class' => 'status-noshow',
                    ],

                    default => [
                        'label' => ucfirst($appointment->status->value),
                        'class' => 'status-default',
                    ],
                };
            @endphp


            <article class="history-card">

                <div class="history-date">

                    <strong>
                        {{ $appointment->starts_at->format('d') }}
                    </strong>

                    <span>
                        {{ strtoupper($appointment->starts_at->locale('nl')->translatedFormat('M')) }}
                    </span>

                </div>


                <div class="history-main">

                    <strong>
                        {{ $appointment->service->name }}
                    </strong>

                    <span class="muted">
                        {{ $appointment->starts_at->format('H:i') }}
                    </span>

                </div>


                <span class="status {{ $status['class'] }}">
                    {{ $status['label'] }}
                </span>

            </article>

        @empty

            <div class="empty-card small-empty">

                <p class="muted">
                    Je hebt nog geen afspraakgeschiedenis.
                </p>

            </div>

        @endforelse

    </section>

@endsection

