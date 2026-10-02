<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, viewport-fit=cover"
    >

    <meta name="theme-color" content="#0b0b0b">

    <title>@yield('title', $title ?? 'JF Cuts')</title>

    <link
        rel="stylesheet"
        href="{{ asset('css/customer.css') }}"
    >
</head>

<body>

<div class="app-shell">

    <header class="topbar">

        <a
            href="{{ route('barber.dashboard') }}"
            class="brand"
            aria-label="JF Cuts"
        >
            <span class="brand-mark">JF</span>

            <span class="brand-name">
                CUTS
            </span>
        </a>

        <form
            method="POST"
            action="{{ route('logout') }}"
        >
            @csrf

            <button
                class="logout-button"
                type="submit"
            >
                Uitloggen
            </button>
        </form>

    </header>


    <main class="page">

        @hasSection('content')
            @yield('content')
        @else
            {{ $slot ?? '' }}
        @endif

    </main>


    <nav class="bottom-nav">

        <a
            href="{{ route('barber.dashboard') }}"
            class="nav-item {{ request()->routeIs('barber.dashboard') ? 'active' : '' }}"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M3 10.5 12 3l9 7.5"/>
                <path d="M5 9.5V21h14V9.5"/>
                <path d="M9 21v-6h6v6"/>
            </svg>

            <span>Home</span>
        </a>


        <a
            href="{{ route('barber.appointments') }}"
            class="nav-item {{ request()->routeIs('barber.appointments*') ? 'active' : '' }}"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <rect x="3" y="5" width="18" height="16" rx="3"/>
                <path d="M16 3v4M8 3v4M3 10h18"/>
            </svg>

            <span>Afspraken</span>
        </a>


        <a
            href="{{ route('barber.appointments.new') }}"
            class="nav-item nav-book {{ request()->routeIs('barber.appointments.new') ? 'active' : '' }}"
        >
            <span class="book-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
            </span>

            <span>Boeken</span>
        </a>


        <a
            href="{{ route('barber.customers') }}"
            class="nav-item {{ request()->routeIs('barber.customers*') ? 'active' : '' }}"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="9" cy="8" r="3"/>
                <path d="M3 20c.6-3.2 2.6-5 6-5s5.4 1.8 6 5"/>
                <path d="M16 11a3 3 0 1 0 0-6"/>
                <path d="M17 15c2.2.4 3.5 2 4 5"/>
            </svg>

            <span>Klanten</span>
        </a>


        <a
            href="{{ route('barber.settings') }}"
            class="nav-item {{ request()->routeIs('barber.settings') ? 'active' : '' }}"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-1.7 1.7-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.1h-2.4v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L8 17l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.6-1H6v-2.4h.8a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L8 8.6 9.7 7l.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.6v-.1h2.4v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3L18 7l1.7 1.6-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.1V14h-.1a1.7 1.7 0 0 0-1.5 1Z"/>
            </svg>

            <span>Instellingen</span>
        </a>

    </nav>

</div>

</body>
</html>

