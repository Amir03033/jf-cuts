@extends('layouts.barber')

@section('title', $title)

@section('content')

    <a href="{{ route('barber.dashboard') }}" class="muted">
        ← Terug naar dashboard
    </a>

    <h1>{{ $title }}</h1>

    <div class="card">
        <p class="muted">
            Deze pagina wordt binnenkort toegevoegd.
        </p>
    </div>

@endsection
