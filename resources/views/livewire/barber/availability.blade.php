<div>

    <a href="{{ route('barber.dashboard') }}" class="muted">
        ← Terug naar dashboard
    </a>

    <h1>Beschikbaarheid</h1>

    <p class="muted">
        Stel in wanneer klanten een afspraak kunnen boeken.
    </p>

    @if (session('status'))
        <div class="card">
            <p>{{ session('status') }}</p>
        </div>
    @endif

    <p class="label spaced">Openingstijden</p>

    <div class="card">

        @foreach ($days as $dayNumber => $day)
            <div class="availability-row">

                <div class="availability-day">
                    <strong>{{ $day['name'] }}</strong>
                </div>

                <label class="availability-toggle">
                    <input
                        type="checkbox"
                        wire:model.live="days.{{ $dayNumber }}.available"
                    >

                    <span>
                        {{ $day['available'] ? 'Open' : 'Gesloten' }}
                    </span>
                </label>

                @if ($day['available'])
                    <input
                        type="time"
                        wire:model="days.{{ $dayNumber }}.start_time"
                    >

                    <span>tot</span>

                    <input
                        type="time"
                        wire:model="days.{{ $dayNumber }}.end_time"
                    >
                @endif

            </div>
        @endforeach

    </div>

    <button
        type="button"
        wire:click="save"
        class="btn btn-primary"
    >
        Beschikbaarheid opslaan
    </button>


    <p class="label spaced">Uitzondering toevoegen</p>

    <div class="card">

        <div class="form-group">
            <label>Datum</label>

            <input
                type="date"
                wire:model="exceptionDate"
            >

            @error('exceptionDate')
            <small class="error">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">

            <label class="availability-toggle">
                <input
                    type="checkbox"
                    wire:model.live="exceptionAvailable"
                >

                <span>
                    {{ $exceptionAvailable
                        ? 'Beschikbaar op deze datum'
                        : 'Gesloten op deze datum'
                    }}
                </span>
            </label>

        </div>

        @if ($exceptionAvailable)

            <div class="time-row">

                <div class="form-group">
                    <label>Van</label>

                    <input
                        type="time"
                        wire:model="exceptionStartTime"
                    >

                    @error('exceptionStartTime')
                    <small class="error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Tot</label>

                    <input
                        type="time"
                        wire:model="exceptionEndTime"
                    >

                    @error('exceptionEndTime')
                    <small class="error">{{ $message }}</small>
                    @enderror
                </div>

            </div>

        @endif

        <button
            type="button"
            wire:click="addException"
            class="btn btn-primary"
        >
            Uitzondering opslaan
        </button>

    </div>


    <p class="label spaced">Uitzonderingen</p>

    @forelse ($exceptions as $exception)

        <div class="card exception-card">

            <div>
                <strong>
                    {{ \Carbon\CarbonImmutable::parse($exception['date'])
                        ->locale('nl')
                        ->translatedFormat('l j F Y') }}
                </strong>

                <p class="muted">

                    @if (! $exception['is_available'])
                    Gesloten
                    @else
                    {{ $exception['start_time'] }}
                    -
                    {{ $exception['end_time'] }}
                    @endif

                </p>
            </div>

            <button
                type="button"
                wire:click="deleteException({{ $exception['id'] }})"
                wire:confirm="Weet je zeker dat je deze uitzondering wilt verwijderen?"
                class="btn btn-outline"
            >
                Verwijderen
            </button>

        </div>

    @empty

        <div class="card">
            <p class="muted">
                Er zijn nog geen uitzonderingen ingesteld.
            </p>
        </div>

    @endforelse

</div>
