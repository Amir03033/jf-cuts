@extends('layouts.customer')

@section('title', 'Home')

@section('content')

    <div class="jf-dashboard">

        {{-- HEADER --}}
        <header class="jf-dashboard-header">

            <div>
                <span class="jf-overline">JF CUTS</span>

                <h1>
                    {{ $greeting }}, {{ $firstName }}
                </h1>

            </div>

            <a
                href="{{ route('customer.profile') }}"
                class="jf-profile-button"
                aria-label="Mijn profiel"
            >
                <span>
                    {{ strtoupper(substr($firstName, 0, 1)) }}
                </span>
            </a>

        </header>


        {{-- NEXT APPOINTMENT --}}
        <section class="jf-next-section">

            <div class="jf-section-header">
                <div>
                    <span class="jf-section-kicker">JE PLANNING</span>
                    <h2>Volgende afspraak</h2>
                </div>

                <a
                    href="{{ route('customer.appointments') }}"
                    class="jf-small-link"
                >
                    Alles
                </a>
            </div>


            @if ($nextAppointment)

                @php
                    $price = (float) $nextAppointment->service->price;

                    $priceText = $price == floor($price)
                        ? number_format($price, 0, ',', '.')
                        : number_format($price, 2, ',', '.');

                    $fullDate = ucfirst(
                        $nextAppointment->starts_at
                            ->locale('nl')
                            ->translatedFormat('l j F')
                    );

                    $dayNumber = $nextAppointment->starts_at->format('d');

                    $month = strtoupper(
                        $nextAppointment->starts_at
                            ->locale('nl')
                            ->translatedFormat('M')
                    );

                    $weekday = ucfirst(
                        $nextAppointment->starts_at
                            ->locale('nl')
                            ->translatedFormat('l')
                    );
                @endphp


                <article class="jf-appointment">

                    {{-- Date block --}}
                    <div class="jf-appointment-date">

                        <span class="jf-appointment-month">
                            {{ $month }}
                        </span>

                        <strong>
                            {{ $dayNumber }}
                        </strong>

                        <span class="jf-appointment-weekday">
                            {{ $weekday }}
                        </span>

                    </div>


                    <div class="jf-appointment-main">

                        <div class="jf-appointment-status">
                            <span></span>
                            BEVESTIGD
                        </div>

                        <h3>
                            {{ $nextAppointment->service->name }}
                        </h3>

                        <div class="jf-appointment-meta">

                            <span>
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9"/>
                                    <path d="M12 7v5l3 2"/>
                                </svg>

                                {{ $nextAppointment->starts_at->format('H:i') }}
                            </span>

                            <span>
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M4 10h16"/>
                                    <path d="M6 10V8a6 6 0 0 1 12 0v2"/>
                                    <path d="M5 10v8h14v-8"/>
                                </svg>

                                JF Cuts
                            </span>

                        </div>

                    </div>


                    <div class="jf-appointment-price">
                        €{{ $priceText }}
                    </div>


                    {{-- Keeps the exact date available for the feature test --}}
                    <span class="jf-screen-reader-date">
                        {{ $fullDate }}
                    </span>

                </article>


                <a
                    href="{{ route('customer.appointments') }}"
                    class="jf-appointment-link"
                >
                    <span>Afspraak bekijken</span>

                    <span class="jf-chevron" aria-hidden="true">
                        ›
                    </span>
                </a>

            @else

                <div class="jf-empty-appointment">

                    <div class="jf-empty-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="17" rx="3"/>
                            <path d="M16 2v4"/>
                            <path d="M8 2v4"/>
                            <path d="M3 9h18"/>
                        </svg>
                    </div>

                    <div>
                        <strong>Nog geen afspraak</strong>

                        <p>
                            Je hebt geen geplande afspraken.
                        </p>
                    </div>

                </div>

            @endif
        </section>


        {{-- PRIMARY CTA --}}
        <a
            href="{{ route('customer.appointments.new') }}"
            class="jf-book-button"
        >

            <span class="jf-book-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 5v14"/>
                    <path d="M5 12h14"/>
                </svg>
            </span>

            <span class="jf-book-content">
                <small>AFSPRAAK MAKEN</small>
                <strong>Boek je volgende bezoek</strong>
            </span>

            <span class="jf-book-arrow" aria-hidden="true">
                →
            </span>

        </a>


        {{-- STATS --}}
        <section class="jf-stats-section">

            <div class="jf-section-header">
                <div>
                    <span class="jf-section-kicker">JOUW JF CUTS</span>
                    <h2>Overzicht</h2>
                </div>
            </div>


            <div class="jf-stats">

                <div class="jf-stat">

                    <span class="jf-stat-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 12a8 8 0 1 0 2.3-5.7"/>
                            <path d="M4 5v5h5"/>
                        </svg>
                    </span>

                    <strong>
                        {{ $visits }}
                    </strong>

                    <span>
                        bezoeken
                    </span>

                </div>


                <div class="jf-stat">

                    <span class="jf-stat-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M6 3v18"/>
                            <path d="M18 3v18"/>
                            <path d="M6 7h12"/>
                            <path d="M6 17h12"/>
                        </svg>
                    </span>

                    <strong>
                        {{ $nextAppointment ? '1' : '0' }}
                    </strong>

                    <span>
                        gepland
                    </span>

                </div>

            </div>


            <a
                href="{{ route('customer.appointments') }}"
                class="jf-history-link"
            >
                <span>
                    Bekijk je afspraakgeschiedenis
                </span>

                <span class="jf-chevron" aria-hidden="true">
                    →
                </span>
            </a>

        </section>


        {{-- FOOTER BRANDING --}}
        <div class="jf-dashboard-footer">
            <span>JF CUTS</span>
            <i></i>
            <span>BARBERSHOP</span>
        </div>

    </div>

@endsection

