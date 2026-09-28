<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ \App\Models\Setting::get('company_name', 'PT Alfajar Logic Futura') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('assets/icons/alf.png') }}" />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased h-screen overflow-hidden">
        <!-- Fixed background: stays put, never scrolls with the content -->
        <div class="fixed inset-0 bg-gradient-to-br from-steel-100 via-white to-brand-50 overflow-hidden">
            <div class="absolute -top-24 -left-24 h-72 w-72 rounded-full bg-brand/10 blur-3xl"></div>
            <div class="absolute -bottom-24 -right-24 h-96 w-96 rounded-full bg-steel-400/10 blur-3xl"></div>
        </div>

        <!-- Scrollable foreground: only this scrolls if content is taller than the viewport -->
        <div class="relative h-screen overflow-y-auto flex flex-col justify-center items-center px-4 py-8">
            <div>
                <a href="/">
                    <x-application-logo class="h-20 w-auto drop-shadow-sm" style="image-rendering: -webkit-optimize-contrast;" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white/90 backdrop-blur-sm shadow-xl ring-1 ring-black/5 overflow-hidden sm:rounded-xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
