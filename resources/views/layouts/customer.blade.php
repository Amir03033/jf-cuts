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
            href="{{ route('customer.dashboard') }}"
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
            href="{{ route('customer.dashboard') }}"
            class="nav-item {{ request()->routeIs('customer.dashboard') ? 'active' : '' }}"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M3 10.5 12 3l9 7.5"/>
                <path d="M5 9.5V21h14V9.5"/>
                <path d="M9 21v-6h6v6"/>
            </svg>

            <span>Home</span>
        </a>


        <a
            href="{{ route('customer.appointments') }}"
            class="nav-item {{ request()->routeIs('customer.appointments') || request()->routeIs('customer.appointments.edit') ? 'active' : '' }}"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <rect x="3" y="5" width="18" height="16" rx="3"/>
                <path d="M16 3v4M8 3v4M3 10h18"/>
            </svg>

            <span>Afspraken</span>
        </a>


        <a
            href="{{ route('customer.appointments.new') }}"
            class="nav-item nav-book {{ request()->routeIs('customer.appointments.new') ? 'active' : '' }}"
        >
            <span class="book-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
            </span>

            <span>Boeken</span>
        </a>


        <a
            href="{{ route('customer.profile') }}"
            class="nav-item {{ request()->routeIs('customer.profile') ? 'active' : '' }}"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="8" r="4"/>
                <path d="M4 21c.8-4 3.4-6 8-6s7.2 2 8 6"/>
            </svg>

            <span>Profiel</span>
        </a>

    </nav>

</div>

</body>
</html>

