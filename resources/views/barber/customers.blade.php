@extends('layouts.barber')

@section('title', 'Klanten')

@section('content')

    <a href="{{ route('barber.dashboard') }}" class="muted">
        ← Terug naar dashboard
    </a>

    <h1>Klanten</h1>

    <p class="muted">
        Bekijk de klanten van {{ $barbershop->name }}.
    </p>

    <form
        method="GET"
        action="{{ route('barber.customers') }}"
        style="margin-top: 20px;"
    >
        <label for="search">
            Klant zoeken
        </label>

        <input
            id="search"
            type="search"
            name="search"
            value="{{ $search }}"
            placeholder="Naam of e-mail"
        >

        <button
            type="submit"
            class="btn btn-primary"
            style="margin-top: 8px;"
        >
            Zoeken
        </button>
    </form>

    <p class="label spaced">
        Klanten
    </p>

    @forelse ($customers as $customer)

        <div class="card">

            <div class="when">
                {{ $customer->name }}
            </div>

            <p class="muted">
                {{ $customer->email }}
            </p>

            @if ($customer->phone)
                <p class="muted">
                    {{ $customer->phone }}
                </p>
            @endif

            <a
                href="{{ route(
                    'barber.customers.show',
                    $customer
                ) }}"
                class="btn btn-outline"
                style="margin-top: 12px;"
            >
                Klant bekijken
            </a>

        </div>

    @empty

        <div class="card">
            <p class="muted">
                @if ($search)
                Geen klanten gevonden voor "{{ $search }}".
                @else
                Er zijn nog geen klanten.
                @endif
            </p>
        </div>

    @endforelse

@endsection
