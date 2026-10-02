<div class="booking-page">

    @if ($step > 1)

        <button
            wire:click="back"
            type="button"
            class="back"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="m15 18-6-6 6-6"/>
            </svg>

            Terug
        </button>

    @endif


    <div class="booking-progress-header">

        <div>

            <span class="eyebrow">
                JE AFSPRAAK
            </span>

            <p class="steps">
                Stap {{ $step }} van 4
            </p>

        </div>

        <span class="progress-number">
            {{ $step * 25 }}%
        </span>

    </div>


    <div
        class="progress"
        aria-hidden="true"
    >
        <div style="width: {{ $step * 25 }}%"></div>
    </div>


    <h1 class="booking-title">

        @switch($step)

            @case(1)
                Kies je behandeling
                @break

            @case(2)
                Kies een datum
                @break

            @case(3)
                Kies een tijd
                @break

            @default
                Controleer je afspraak

        @endswitch

    </h1>


    @if ($error)

        <div
            class="error-message"
            role="alert"
        >
            {{ $error }}
        </div>

    @endif


    <div wire:loading.class="is-loading">


        {{-- BEHANDELING --}}

        @if ($step === 1)

            <p class="booking-description">
                Selecteer de behandeling die je wilt boeken.
            </p>


            <div class="booking-options">

                @forelse ($this->services as $service)

                    <button
                        wire:click="selectService({{ $service->id }})"
                        type="button"
                        class="booking-option"
                    >

                        <span class="booking-option-icon">

                            @if (str_contains(strtolower($service->name), 'verf'))

                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M4 17c5-1 9-5 10-10l1-3 3 3-3 1c-5 1-9 5-10 10l-1 3-3-3 3-1Z"/>
                                </svg>

                            @else

                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M6 7h12"/>
                                    <path d="M8 7v4a4 4 0 0 0 8 0V7"/>
                                    <path d="M12 15v6"/>
                                    <path d="M8 21h8"/>
                                </svg>

                            @endif

                        </span>


                        <span class="booking-option-content">

                            <strong>
                                {{ $service->name }}
                            </strong>

                            <small>
                                {{ $service->duration }} minuten
                            </small>

                        </span>


                        <span class="booking-option-price">
                            €{{ number_format($service->price, 0, ',', '.') }}
                        </span>


                        <svg
                            class="booking-option-arrow"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path d="m9 18 6-6-6-6"/>
                        </svg>

                    </button>

                @empty

                    <div class="empty-card">

                        <p class="muted">
                            Er zijn nog geen behandelingen beschikbaar.
                        </p>

                    </div>

                @endforelse

            </div>

        @endif


        {{-- DATUM --}}

        @if ($step === 2)

            <p class="booking-description">
                Kies een beschikbare dag voor je afspraak.
            </p>


            <div class="calendar-grid">

                @foreach ($this->days as $day)

                    @php
                        $d = $day['date'];
                    @endphp

                    <button
                        wire:click="selectDate('{{ $d->toDateString() }}')"
                        type="button"
                        class="date-chip {{ $day['open'] ? '' : 'disabled' }}"
                        @disabled(! $day['open'])
                    >

                        <small>
                            {{ $d->translatedFormat('D') }}
                        </small>

                        <strong>
                            {{ $d->format('j') }}
                        </strong>

                        <small>
                            {{ $d->translatedFormat('M') }}
                        </small>

                    </button>

                @endforeach

            </div>


            <div class="calendar-note">

                <span class="note-dot"></span>

                <span>
                    Grijze dagen zijn gesloten of volgeboekt.
                </span>

            </div>

        @endif


        {{-- TIJD --}}

        @if ($step === 3)

            <div class="selected-date">

                <span class="selected-date-icon">

                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="5" width="18" height="16" rx="3"/>
                        <path d="M16 3v4M8 3v4M3 10h18"/>
                    </svg>

                </span>

                <span>
                    {{ \Carbon\CarbonImmutable::parse($date)->locale('nl')->translatedFormat('l j F') }}
                </span>

            </div>


            <p class="booking-description">
                Kies een beschikbare tijd.
            </p>


            @if (empty($availableSlots))

                <div class="empty-card">

                    <div class="empty-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 7v5l3 2"/>
                        </svg>
                    </div>

                    <h2>
                        Geen tijden beschikbaar
                    </h2>

                    <p class="muted">
                        Kies een andere datum.
                    </p>

                </div>

            @else

                <div class="time-grid">

                    @foreach ($availableSlots as $slot)

                        <button
                            wire:click="selectTime('{{ $slot }}')"
                            type="button"
                            class="time-slot"
                        >
                            {{ $slot }}
                        </button>

                    @endforeach

                </div>

            @endif

        @endif


        {{-- BEVESTIGEN --}}

        @if ($step === 4)

            <p class="booking-description">
                Controleer je afspraak voordat je deze bevestigt.
            </p>


            <div class="confirmation-card">

                <div class="confirmation-top">

                    <span class="confirmation-label">
                        AFSPRAAK
                    </span>

                    <span class="status status-scheduled">
                        Nieuw
                    </span>

                </div>


                <div class="confirmation-date">

                    <span>
                        {{ \Carbon\CarbonImmutable::parse($date)->locale('nl')->translatedFormat('l') }}
                    </span>

                    <strong>
                        {{ \Carbon\CarbonImmutable::parse($date)->format('j') }}
                    </strong>

                    <span>
                        {{ \Carbon\CarbonImmutable::parse($date)->locale('nl')->translatedFormat('F Y') }}
                    </span>

                </div>


                <div class="confirmation-time">
                    {{ $time }}
                </div>


                <div class="confirmation-divider"></div>


                <div class="confirmation-row">

                    <span>
                        Behandeling
                    </span>

                    <strong>
                        {{ $this->selectedService->name }}
                    </strong>

                </div>


                <div class="confirmation-row">

                    <span>
                        Duur
                    </span>

                    <strong>
                        {{ $this->selectedService->duration }} min
                    </strong>

                </div>


                <div class="confirmation-row">

                    <span>
                        Prijs
                    </span>

                    <strong class="accent-text">
                        €{{ number_format($this->selectedService->price, 0, ',', '.') }}
                    </strong>

                </div>


                <div class="confirmation-payment">

                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="6" width="18" height="12" rx="2"/>
                        <path d="M3 10h18"/>
                        <path d="M7 14h4"/>
                    </svg>

                    <span>
                        Betaling gebeurt contant bij JF Cuts.
                    </span>

                </div>

            </div>


            <button
                wire:click="confirm"
                wire:loading.attr="disabled"
                type="button"
                class="btn btn-primary booking-confirm-button"
            >

                <span
                    wire:loading.remove
                    wire:target="confirm"
                >
                    Afspraak bevestigen
                </span>

                <span
                    wire:loading
                    wire:target="confirm"
                >
                    Bezig met boeken…
                </span>

                <svg
                    wire:loading.remove
                    wire:target="confirm"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path d="M5 12h14"/>
                    <path d="m13 6 6 6-6 6"/>
                </svg>

            </button>


            <p class="booking-policy">
                Je kunt tot 1 uur voor je afspraak verplaatsen of annuleren.
            </p>

        @endif

    </div>

</div>
