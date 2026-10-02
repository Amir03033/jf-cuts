@extends('layouts.barber')

@section('title', 'Dienst bewerken')

@section('content')

    <a href="{{ route('barber.services') }}" class="muted">
        ← Terug naar diensten
    </a>

    <h1>Dienst bewerken</h1>

    <p class="muted">
        Pas {{ $service->name }} aan.
    </p>

    <form
        method="POST"
        action="{{ route(
            'barber.services.update',
            $service
        ) }}"
    >
        @csrf
        @method('PUT')

        <label for="name">
            Naam
        </label>

        <input
            id="name"
            type="text"
            name="name"
            value="{{ old('name', $service->name) }}"
            required
        >

        @error('name')
        <p class="error">{{ $message }}</p>
        @enderror

        <label for="duration" style="margin-top: 16px;">
            Duur in minuten
        </label>

        <input
            id="duration"
            type="number"
            name="duration"
            value="{{ old('duration', $service->duration) }}"
            min="1"
            required
        >

        @error('duration')
        <p class="error">{{ $message }}</p>
        @enderror

        <label for="price" style="margin-top: 16px;">
            Prijs
        </label>

        <input
            id="price"
            type="number"
            name="price"
            value="{{ old('price', $service->price) }}"
            min="0"
            step="0.01"
            required
        >

        @error('price')
        <p class="error">{{ $message }}</p>
        @enderror

        <label style="display: block; margin-top: 16px;">
            <input
                type="checkbox"
                name="active"
                value="1"
                @checked(old('active', $service->active))
            >

            Actief
        </label>

        <button
            type="submit"
            class="btn btn-primary"
            style="margin-top: 20px;"
        >
            Wijzigingen opslaan
        </button>

    </form>

@endsection
