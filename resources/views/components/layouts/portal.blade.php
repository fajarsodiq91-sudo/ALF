@props(['title' => 'Customer Portal'])
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'Customer Portal' }} | PT Alfajar Logic Futura</title>
        <link rel="icon" type="image/png" href="{{ asset('assets/icons/alf.png') }}" />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 min-h-screen bg-gradient-to-br from-steel-50 to-steel-100">
        @php $preview = session()->has('portal_preview'); @endphp
        @if ($preview)
            <div class="bg-amber-400 px-4 py-2 text-center text-sm font-medium text-amber-950 shadow">
                Preview mode: you are viewing the portal as {{ auth('customer')->user()?->name }}. This view is read-only.
            </div>
        @endif
        <header class="bg-gradient-to-r from-steel-900 via-brand-dark to-brand-light text-white shadow-md">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-3">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('assets/icons/alf.png') }}" alt="" class="h-9 w-9 rounded">
                    <span class="text-sm font-semibold leading-tight">PT Alfajar Logic Futura<br><span class="text-xs font-normal text-white/70">Customer Portal</span></span>
                </div>
                @auth('customer')
                    <div class="flex items-center gap-4 text-sm">
                        <span class="hidden sm:inline text-white/80">{{ auth('customer')->user()->name }} · <span class="font-mono">{{ auth('customer')->user()->customer_code }}</span></span>
                        @unless ($preview)
                            <a href="{{ route('portal.password.edit') }}" class="text-white/80 hover:text-white">Password</a>
                        @endunless
                        <form action="{{ route('portal.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="rounded-md bg-white/10 px-3 py-1.5 font-medium hover:bg-white/20 transition">{{ $preview ? 'Exit preview' : 'Log out' }}</button>
                        </form>
                    </div>
                @endauth
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
            @endif
            {{ $slot }}
        </main>
    </body>
</html>
