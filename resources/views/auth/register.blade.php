@extends('layouts.app')

@section('content')
    <div class="card">
        <img
            src="{{ asset('images/barber.jpg') }}"
            alt="JF Cuts barber"
            class="auth-image"
        >
        <h1>Account aanmaken</h1>

        <form method="POST" action="{{ url('/registreren') }}">
            @csrf

            <label for="name">Naam</label>
            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                autocomplete="name"
                required
                autofocus
            >
            @error('name')
            <div class="error">{{ $message }}</div>
            @enderror

            <label for="email">E-mailadres</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                autocomplete="email"
                required
            >
            @error('email')
            <div class="error">{{ $message }}</div>
            @enderror

            <label for="phone">
                Telefoonnummer
                <span style="color:#888;">(optioneel)</span>
            </label>
            <input
                id="phone"
                type="tel"
                name="phone"
                value="{{ old('phone') }}"
                autocomplete="tel"
            >
            @error('phone')
            <div class="error">{{ $message }}</div>
            @enderror

            <label for="password">Wachtwoord</label>
            <input
                id="password"
                type="password"
                name="password"
                autocomplete="new-password"
                required
            >
            @error('password')
            <div class="error">{{ $message }}</div>
            @enderror

            <button type="submit">Account aanmaken</button>
        </form>

        <div class="link">
            Al een account?
            <a href="{{ route('login') }}">Inloggen</a>
        </div>
    </div>
@endsection
