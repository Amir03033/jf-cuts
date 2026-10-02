@extends('layouts.barber')

@section('title', 'Instellingen')

@section('content')
    <div class="container">

        <div class="card">
            <h1>Shop instellingen</h1>

            @if (session('status'))
                <div class="alert">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('barber.settings.update') }}">
                @csrf
                @method('PUT')

                <h2>Boekingen</h2>

                <div>
                    <label for="booking_interval">
                        Boekingsinterval
                    </label>

                    <select
                        id="booking_interval"
                        name="booking_interval"
                    >
                        @foreach ([15, 30, 45, 60, 90, 120] as $interval)
                            <option
                                value="{{ $interval }}"
                                @selected(
                                    old(
                                        'booking_interval',
                                        $settings->booking_interval
                                    ) == $interval
                                )
                            >
                                {{ $interval }} minuten
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="max_booking_days">
                        Maximaal dagen vooruit boeken
                    </label>

                    <input
                        id="max_booking_days"
                        type="number"
                        name="max_booking_days"
                        min="1"
                        max="365"
                        value="{{ old(
                            'max_booking_days',
                            $settings->max_booking_days
                        ) }}"
                    >
                </div>

                <div>
                    <label for="cancellation_limit">
                        Annuleren tot hoeveel minuten voor afspraak
                    </label>

                    <input
                        id="cancellation_limit"
                        type="number"
                        name="cancellation_limit"
                        min="0"
                        max="1440"
                        value="{{ old(
                            'cancellation_limit',
                            $settings->cancellation_limit
                        ) }}"
                    >
                </div>

                <div>
                    <label for="reschedule_limit">
                        Verplaatsen tot hoeveel minuten voor afspraak
                    </label>

                    <input
                        id="reschedule_limit"
                        type="number"
                        name="reschedule_limit"
                        min="0"
                        max="1440"
                        value="{{ old(
                            'reschedule_limit',
                            $settings->reschedule_limit
                        ) }}"
                    >
                </div>

                <hr>

                <h2>Openingstijden</h2>

                @php
                    $days = [
                        1 => 'Maandag',
                        2 => 'Dinsdag',
                        3 => 'Woensdag',
                        4 => 'Donderdag',
                        5 => 'Vrijdag',
                        6 => 'Zaterdag',
                        7 => 'Zondag',
                    ];
                @endphp

                @foreach ($days as $dayNumber => $dayName)
                    @php
                        $rule = $availabilityRules->get($dayNumber);
                    @endphp

                    <div class="opening-day">
                        <h3>{{ $dayName }}</h3>

                        <label>
                            <input
                                type="checkbox"
                                name="opening_hours[{{ $dayNumber }}][is_available]"
                                value="1"
                                @checked(
                                    old(
                                        "opening_hours.$dayNumber.is_available",
                                        $rule?->is_available ?? false
                                    )
                                )
                            >

                            Open
                        </label>

                        <div>
                            <label>
                                Van

                                <input
                                    type="time"
                                    name="opening_hours[{{ $dayNumber }}][start_time]"
                                    value="{{ old(
                                        "opening_hours.$dayNumber.start_time",
                                        $rule?->start_time
                                            ? substr($rule->start_time, 0, 5)
                                            : ''
                                    ) }}"
                                >
                            </label>

                            <label>
                                Tot

                                <input
                                    type="time"
                                    name="opening_hours[{{ $dayNumber }}][end_time]"
                                    value="{{ old(
                                        "opening_hours.$dayNumber.end_time",
                                        $rule?->end_time
                                            ? substr($rule->end_time, 0, 5)
                                            : ''
                                    ) }}"
                                >
                            </label>
                        </div>
                    </div>
                @endforeach

                <button type="submit">
                    Instellingen opslaan
                </button>
            </form>
        </div>

    </div>
@endsection
