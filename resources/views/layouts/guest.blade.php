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
        <div class="relative min-h-screen overflow-hidden bg-slate-950">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(45,212,191,0.22),_transparent_40%),radial-gradient(circle_at_bottom_right,_rgba(14,165,233,0.18),_transparent_35%)]"></div>

            <div class="relative flex min-h-screen flex-col justify-center px-4 py-10 sm:px-6 lg:px-8">
                <div class="mx-auto mb-8">
                    <a href="{{ url('/') }}" class="rounded-xl focus-visible:ring-2 focus-visible:ring-brand-400">
                        <x-application-logo class="text-white [&_span:last-child]:text-white" />
                    </a>
                </div>

                <div class="mx-auto w-full max-w-md">
                    <x-card class="shadow-soft">
                        {{ $slot }}
                    </x-card>
                </div>
            </div>
        </div>
    </body>
</html>
