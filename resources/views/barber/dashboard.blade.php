@extends('layouts.app')
@section('content')
    <div class="card">
        <h1>Kapperdashboard</h1>
        <p>Welkom, {{ auth()->user()->name }}.</p>
        <form method="POST" action="{{ route('logout') }}">@csrf<button>Uitloggen</button></form>
    </div>
@endsection
