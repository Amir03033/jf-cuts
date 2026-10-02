@extends('layouts.app')

@section('content')
    <div class="card">
        <img
            src="{{ asset('images/barber.jpg') }}"
            alt="JF Cuts barber"
            class="auth-image"
        >
        <h1>Welkom bij JF Cuts</h1>

        <form method="POST" action="{{ url('/inloggen') }}">
            @csrf

            <label for="email">E-mailadres</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                autocomplete="email"
                required
                autofocus
            >
            @error('email')
            <div class="error">{{ $message }}</div>
            @enderror

            <label for="password">Wachtwoord</label>
            <input
                id="password"
                type="password"
                name="password"
                autocomplete="current-password"
                required
            >
            @error('password')
            <div class="error">{{ $message }}</div>
            @enderror

            <button type="submit">Inloggen</button>
        </form>

        <div class="link">
            Nog geen account?
            <a href="{{ route('register') }}">Registreren</a>
        </div>
    </div>
@endsection
