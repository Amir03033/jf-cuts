<div>


    <a href="{{ route('barber.appointments') }}" class="muted">
        ← Terug naar afspraken
    </a>

    <h1>Nieuwe afspraak</h1>

    <p class="muted">
        Maak een afspraak voor een bestaande klant.
    </p>


    {{-- STAP 1: KLANT --}}
    @if ($step === 1)

        <p class="label spaced">
            1. Kies een klant
        </p>

        <div class="form-group">

            <label for="search">
                Zoek klant
            </label>

            <input
                id="search"
                type="text"
                wire:model.live="search"
                placeholder="Naam of e-mail"
            >

        </div>

        @forelse ($this->customers as $customer)

            <button
                type="button"
                wire:click="selectCustomer({{ $customer->id }})"
                class="btn btn-outline"
                style="width: 100%; margin-bottom: 8px; text-align: left;"
            >
                <strong>
                    {{ $customer->name }}
                </strong>

                <br>

                <span class="muted">
                {{ $customer->email }}
            </span>
            </button>

        @empty

            <div class="card">

                @if (trim($search) !== '')

                    <p class="muted">
                        Geen klant gevonden.
                    </p>

                    <p class="muted">
                        De klant moet eerst zelf een account aanmaken.
                    </p>

                @else

                    <p class="muted">
                        Zoek hierboven naar een bestaande klant.
                    </p>

                @endif

            </div>

        @endforelse

    @endif


    {{-- STAP 2: BEHANDELING --}}
    @if ($step === 2)

        <p class="label spaced">
            2. Kies een behandeling
        </p>

        <div class="card">

            <p class="muted">
                Klant
            </p>

            <strong>
                {{ $this->selectedCustomer?->name }}
            </strong>

        </div>

        @forelse ($this->services as $service)

            <button
                type="button"
                wire:click="selectService({{ $service->id }})"
                class="btn btn-outline"
                style="width: 100%; margin-bottom: 8px; text-align: left;"
            >

                <strong>
                    {{ $service->name }}
                </strong>

                <br>

                <span class="muted">
                {{ $service->duration }} minuten ·
                €{{ number_format($service->price, 2, ',', '.') }}
            </span>

            </button>

        @empty

            <div class="card">
                <p class="muted">
                    Er zijn geen actieve behandelingen.
                </p>
            </div>

        @endforelse

        <button
            type="button"
            wire:click="back"
            class="btn btn-outline"
            style="margin-top: 8px;"
        >
            ← Andere klant
        </button>

    @endif


    {{-- STAP 3: DATUM --}}
    @if ($step === 3)

        <p class="label spaced">
            3. Kies een datum
        </p>

        <div class="card">

            <p class="muted">
                Klant
            </p>

            <strong>
                {{ $this->selectedCustomer?->name }}
            </strong>

            <p class="muted" style="margin-top: 8px;">
                {{ $this->selectedService?->name }}
            </p>

        </div>

        @forelse ($this->days as $day)

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

        <button
            type="button"
            wire:click="back"
            class="btn btn-outline"
            style="margin-top: 8px;"
        >
            ← Andere behandeling
        </button>

    @endif


    {{-- STAP 4: TIJD --}}
    @if ($step === 4)

        <p class="label spaced">
            4. Kies een tijd
        </p>

        <div class="card">

            <p class="muted">
                {{ Carbon\CarbonImmutable::parse($date)
                    ->locale('nl')
                    ->translatedFormat('l j F Y') }}
            </p>

            <strong>
                {{ $this->selectedCustomer?->name }}
            </strong>

            <p class="muted">
                {{ $this->selectedService?->name }}
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
                    Er zijn geen beschikbare tijden op deze dag.
                </p>

            </div>

        @endforelse

        <button
            type="button"
            wire:click="back"
            class="btn btn-outline"
            style="margin-top: 8px;"
        >
            ← Andere datum
        </button>

    @endif


    {{-- STAP 5: BEVESTIGEN --}}
    @if ($step === 5)

        <p class="label spaced">
            5. Bevestig afspraak
        </p>

        @if ($error)

            <div class="card">

                <p class="error">
                    {{ $error }}
                </p>

            </div>

        @endif

        <div class="card">

            <p class="label">
                Klant
            </p>

            <div class="big" style="font-size: 24px;">
                {{ $this->selectedCustomer?->name }}
            </div>

            <p class="muted">
                {{ $this->selectedCustomer?->email }}
            </p>


            <p class="label spaced">
                Behandeling
            </p>

            <div class="service">

            <span>
                {{ $this->selectedService?->name }}
            </span>

                <span>
                €{{ number_format(
                    $this->selectedService?->price,
                    2,
                    ',',
                    '.'
                ) }}
            </span>

            </div>


            <p class="label spaced">
                Datum
            </p>

            <p>
                {{ Carbon\CarbonImmutable::parse($date)
                    ->locale('nl')
                    ->translatedFormat('l j F Y') }}
            </p>


            <p class="label spaced">
                Tijd
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
            Afspraak bevestigen
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
