@extends('layouts.barber')

@section('title', 'Diensten')

@section('content')

    <a href="{{ route('barber.dashboard') }}" class="muted">
        ← Terug naar dashboard
    </a>

    <h1>Diensten</h1>

    <p class="muted">
        Beheer de behandelingen van {{ $barbershop->name }}.
    </p>

    @if (session('status'))
        <div class="card">
            <p>
                {{ session('status') }}
            </p>
        </div>
    @endif

    <a
        href="{{ route('barber.services.new') }}"
        class="btn btn-primary"
        style="margin-top: 16px;"
    >
        + Nieuwe dienst
    </a>

    <p class="label spaced">
        Diensten
    </p>

    @forelse ($services as $service)

        <div class="card">

            <div class="when">
                {{ $service->name }}
            </div>

            <div class="service">
                <span>
                    {{ $service->duration }} minuten
                </span>

                <span>
                    €{{ number_format(
                        $service->price,
                        2,
                        ',',
                        '.'
                    ) }}
                </span>
            </div>

            <p class="muted">
                @if ($service->active)
                Actief
                @else
                Inactief
                @endif
            </p>

            <a
                href="{{ route(
                    'barber.services.edit',
                    $service
                ) }}"
                class="btn btn-outline"
                style="margin-top: 8px;"
            >
                Bewerken
            </a>

        </div>

    @empty

        <div class="card">
            <p class="muted">
                Er zijn nog geen diensten.
            </p>
        </div>

    @endforelse

@endsection
