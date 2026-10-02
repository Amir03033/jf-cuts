@extends('layouts.customer')

@section('title', 'Profiel')

@section('content')

    <section class="profile-header">

        <div class="profile-avatar">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>

        <div>
            <p class="eyebrow">
                Mijn account
            </p>

            <h1>
                {{ $user->name }}
            </h1>

            <p class="muted">
                {{ $user->email }}
            </p>
        </div>

    </section>


    @if (session('status'))

        <div class="success-message">

            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="9"/>
                <path d="m8 12 3 3 5-6"/>
            </svg>

            <span>
                {{ session('status') }}
            </span>

        </div>

    @endif


    @if ($errors->any())

        <div class="error-message">

            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach

        </div>

    @endif


    <section class="section">

        <p class="label">
            Persoonlijke gegevens
        </p>


        <div class="form-card">

            <form
                method="POST"
                action="{{ route('customer.profile.update') }}"
            >
                @csrf
                @method('PUT')


                <div class="form-group">

                    <label for="name">
                        Naam
                    </label>

                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        required
                        autocomplete="name"
                    >

                </div>


                <div class="form-group">

                    <label for="email">
                        E-mailadres
                    </label>

                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        required
                        autocomplete="email"
                    >

                </div>


                <div class="form-group">

                    <label for="phone">
                        Telefoonnummer
                    </label>

                    <input
                        id="phone"
                        type="tel"
                        name="phone"
                        value="{{ old('phone', $user->phone) }}"
                        autocomplete="tel"
                        placeholder="Optioneel"
                    >

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Gegevens opslaan
                </button>

            </form>

        </div>

    </section>


    <section class="section">

        <p class="label">
            Beveiliging
        </p>


        <div class="form-card">

            <form
                method="POST"
                action="{{ route('customer.profile.password') }}"
            >
                @csrf
                @method('PUT')


                <div class="form-group">

                    <label for="current_password">
                        Huidig wachtwoord
                    </label>

                    <input
                        id="current_password"
                        type="password"
                        name="current_password"
                        required
                        autocomplete="current-password"
                    >

                </div>


                <div class="form-group">

                    <label for="password">
                        Nieuw wachtwoord
                    </label>

                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                    >

                </div>


                <div class="form-group">

                    <label for="password_confirmation">
                        Nieuw wachtwoord opnieuw
                    </label>

                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                    >

                </div>


                <button
                    type="submit"
                    class="btn btn-outline"
                >
                    Wachtwoord wijzigen
                </button>

            </form>

        </div>

    </section>


    <section class="section">

        <p class="label">
            Account
        </p>


        <div class="account-action-card">

            <div>
                <strong>
                    Uitloggen
                </strong>

                <span>
                    Log uit op dit apparaat.
                </span>
            </div>


            <form
                method="POST"
                action="{{ route('logout') }}"
            >
                @csrf

                <button
                    type="submit"
                    class="logout-icon-button"
                    aria-label="Uitloggen"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M10 17l5-5-5-5"/>
                        <path d="M15 12H3"/>
                        <path d="M19 3h2v18h-2"/>
                    </svg>
                </button>

            </form>

        </div>

    </section>

@endsection
