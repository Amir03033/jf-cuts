@extends('layouts.customer')

@section('title', $title)

@section('content')
    <h1>{{ $title }}</h1>

    <div class="card">
        <p class="muted">Deze pagina komt binnenkort.</p>
    </div>

    <a class="btn btn-outline" href="{{ route('customer.dashboard') }}">Terug naar home</a>
@endsection
