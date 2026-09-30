@extends('layouts.app')
@section('content')
    <div class="card">
        <h1>JF Cuts</h1>
        <form method="POST" action="{{ url('/inloggen') }}">
            @csrf
            <label for="email">E-mailadres</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email') <div class="error">{{ $message }}</div> @enderror

            <label for="password">Wachtwoord</label>
            <input id="password" type="password" name="password" required>

            <button type="submit">Inloggen</button>
        </form>
        <div class="link">Nog geen account? <a href="{{ route('register') }}">Registreren</a></div>
    </div>
@endsection
