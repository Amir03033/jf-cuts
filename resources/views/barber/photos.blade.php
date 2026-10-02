@php
    use Illuminate\Support\Facades\Storage;
@endphp

@extends('layouts.barber')

@section('title', 'Shopfoto\'s')

@section('content')
    <div class="container">

        <div class="card">
            <h1>Shopfoto's</h1>

            @if (session('status'))
                <div class="alert">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <h2>Nieuwe foto</h2>

            <form
                method="POST"
                action="{{ route('barber.photos.store') }}"
                enctype="multipart/form-data"
            >
                @csrf

                <input
                    type="file"
                    name="image"
                    accept="image/jpeg,image/png,image/webp"
                    required
                >

                <button type="submit">
                    Foto uploaden
                </button>
            </form>
        </div>

        <div class="card">
            <h2>Shopfoto's</h2>

            @if ($images->isEmpty())
                <p>Er zijn nog geen foto's toegevoegd.</p>
            @else
                <div>
                    @foreach ($images as $image)
                        <div>
                            <img
                                src="{{ Storage::disk('public')->url($image->path) }}"
                                alt="Shopfoto"
                                style="max-width: 300px;"
                            >

                            <p>
                                Volgorde: {{ $image->sort_order }}
                            </p>

                            <form
                                method="POST"
                                action="{{ route(
                                    'barber.photos.destroy',
                                    $image
                                ) }}"
                            >
                                @csrf
                                @method('DELETE')

                                <button type="submit">
                                    Verwijderen
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
@endsection
