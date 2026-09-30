@extends('layouts.app')
@section('content')
    <div class="card">
        <h1>Hoi, {{ auth()->user()->name }} 👋</h1>
        <p>Klantdashboard (hier komt de afspraakflow).</p>
        <form method="POST" action="{{ route('logout') }}">@csrf<button>Uitloggen</button></form>
    </div>
@endsection
