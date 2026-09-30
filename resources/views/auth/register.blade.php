@extends('layouts.app')
@section('content')
    <div class="card">
        <h1>Account aanmaken</h1>
        <form method="POST" action="{{ url('/registreren') }}">
            @csrf
            <label for="name">Naam *</label>
            <input id="name" name="name" value="{{ old('name') }}" required>
            @error('name') <div class="error">{{ $message }}</div> @enderror

            <label for="email">E-mailadres *</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required>
            @error('email') <div class="error">{{ $message }}</div> @enderror

            <label for="phone">Telefoonnummer</label>
            <input id="phone" type="tel" name="phone" value="{{ old('phone') }}">
            @error('phone') <div class="error">{{ $message }}</div> @enderror

            <label for="password">Wachtwoord *</label>
            <input id="password" type="password" name="password" required>
            @error('password') <div class="error">{{ $message }}</div> @enderror

            <button type="submit">Account aanmaken</button>
        </form>
        <div class="link">Al een account? <a href="{{ route('login') }}">Inloggen</a></div>
    </div>
@endsection
