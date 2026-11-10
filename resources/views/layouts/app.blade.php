<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SEATBOOK') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=fraunces:600,700|outfit:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="flex min-h-screen flex-col bg-slate-50">
            @include('layouts.navigation')

            @isset($header)
                <header class="border-b border-slate-200/70 bg-white">
                    <div class="seatbook-container py-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="flex-1">
                @if (session('status') || session('error'))
                    <div class="seatbook-container space-y-3 py-4">
                        <x-alert type="status" />
                        <x-alert type="error" />
                    </div>
                @endif

                {{ $slot }}
            </main>

            @include('layouts.footer')
        </div>
    </body>
</html>
