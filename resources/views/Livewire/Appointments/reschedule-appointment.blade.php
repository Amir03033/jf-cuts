<div>

    <a href="{{ route('customer.appointments') }}" class="muted">
        ← Terug naar mijn afspraken
    </a>

    <h1>Afspraak verplaatsen</h1>

    <p class="muted">
        Je verplaatst je afspraak voor
        <strong>{{ $appointment->service->name }}</strong>.
    </p>

    <div class="card">

        <p class="label">Huidige afspraak</p>

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

    </div>

    @if ($error)
        <div class="card">
            <p class="error">
                {{ $error }}
            </p>
        </div>
    @endif


    {{-- STAP 1: DATUM --}}
    @if ($step === 1)

        <p class="label spaced">
            Kies een nieuwe datum
        </p>

        @forelse ($this->days() as $day)

            @if ($day['open'])

                <button
                    type="button"
                    wire:click="selectDate('{{ $day['date']->toDateString() }}')"
                    class="btn btn-outline"
                    style="margin-bottom: 8px; width: 100%;"
                >
                    {{ ucfirst(
                        $day['date']
                            ->locale('nl')
                            ->translatedFormat('l j F')
                    ) }}
                </button>

            @endif

        @empty

            <div class="card">
                <p class="muted">
                    Er zijn geen beschikbare dagen.
                </p>
            </div>

        @endforelse

    @endif


    {{-- STAP 2: TIJD --}}
    @if ($step === 2)

        <p class="label spaced">
            Kies een nieuwe tijd
        </p>

        <p class="muted">
            {{ Carbon\CarbonImmutable::parse($date)
                ->locale('nl')
                ->translatedFormat('l j F Y') }}
        </p>

        @forelse ($availableSlots as $slot)

            <button
                type="button"
                wire:click="selectTime('{{ $slot }}')"
                class="btn btn-outline"
                style="margin-bottom: 8px; width: 100%;"
            >
                {{ $slot }}
            </button>

        @empty

            <div class="card">
                <p class="muted">
                    Er zijn geen beschikbare tijden op deze dag.
                </p>
            </div>

        @endforelse

        <button
            type="button"
            wire:click="back"
            class="btn btn-outline"
        >
            ← Andere datum
        </button>

    @endif


    {{-- STAP 3: BEVESTIGEN --}}
    @if ($step === 3)

        <p class="label spaced">
            Bevestig je nieuwe afspraak
        </p>

        <div class="card">

            <div class="when">
                {{ Carbon\CarbonImmutable::parse($date)
                    ->locale('nl')
                    ->translatedFormat('l j F Y') }}
            </div>

            <div class="time">
                {{ $time }}
            </div>

            <div class="service">
                <span>
                    {{ $appointment->service->name }}
                </span>

                <span>
                    €{{ number_format(
                        $appointment->service->price,
                        0,
                        ',',
                        '.'
                    ) }}
                </span>
            </div>

        </div>

        <button
            type="button"
            wire:click="confirm"
            class="btn btn-primary"
        >
            Afspraak verplaatsen
        </button>

        <button
            type="button"
            wire:click="back"
            class="btn btn-outline"
            style="margin-top: 8px;"
        >
            ← Andere tijd
        </button>

    @endif

</div>
