<div>

    ```
    <a href="{{ route('barber.appointments') }}" class="muted">
        ← Terug naar afspraken
    </a>

    <h1>Afspraak verplaatsen</h1>

    <p class="muted">
        Pas de afspraak van {{ $appointment->customer->name }} aan.
    </p>


    <div class="card">

        <p class="label">
            Huidige afspraak
        </p>

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
            {{ $appointment->customer->name }}
        </span>

        </div>

    </div>


    @if ($error)

        <div class="card">

            <p class="error">
                {{ $error }}
            </p>

        </div>

    @endif


    {{-- DATUM --}}

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
                    style="width: 100%; margin-bottom: 8px;"
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


    {{-- TIJD --}}

    @if ($step === 2)

        <p class="label spaced">
            Kies een nieuwe tijd
        </p>

        <div class="card">

            <p class="muted">
                {{ Carbon\CarbonImmutable::parse($date)
                    ->locale('nl')
                    ->translatedFormat('l j F Y') }}
            </p>

            <strong>
                {{ $appointment->customer->name }}
            </strong>

            <p class="muted">
                {{ $appointment->service->name }}
            </p>

        </div>

        @forelse ($availableSlots as $slot)

            <button
                type="button"
                wire:click="selectTime('{{ $slot }}')"
                class="btn btn-outline"
                style="width: 100%; margin-bottom: 8px;"
            >
                {{ $slot }}
            </button>

        @empty

            <div class="card">

                <p class="muted">
                    Er zijn geen beschikbare tijden.
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


    {{-- BEVESTIGING --}}

    @if ($step === 3)

        <p class="label spaced">
            Bevestig wijziging
        </p>

        <div class="card">

            <p class="label">
                Klant
            </p>

            <strong>
                {{ $appointment->customer->name }}
            </strong>


            <p class="label spaced">
                Behandeling
            </p>

            <p>
                {{ $appointment->service->name }}
            </p>


            <p class="label spaced">
                Nieuwe datum
            </p>

            <p>
                {{ Carbon\CarbonImmutable::parse($date)
                    ->locale('nl')
                    ->translatedFormat('l j F Y') }}
            </p>


            <p class="label spaced">
                Nieuwe tijd
            </p>

            <div class="time">
                {{ $time }}
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
