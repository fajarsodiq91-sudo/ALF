@php($company = \App\Models\Setting::get('company_name', 'PT Alfajar Logic Futura'))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? __('Staff Login') }} | {{ $company }}</title>
        <link rel="icon" type="image/png" href="{{ asset('assets/icons/alf.png') }}" />

        <!-- Fonts: same set as the ERP -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans text-gray-900 antialiased bg-white">
        <div class="flex min-h-full">
            {{-- Brand panel (desktop): matches the ERP sidebar --}}
            <aside class="relative hidden lg:flex lg:w-[44%] xl:w-1/2 flex-col justify-between overflow-hidden bg-gradient-to-br from-steel-900 via-steel-800 to-brand-950 p-10 xl:p-14 text-white">
                {{-- Soft glows and a faint grid --}}
                <div class="pointer-events-none absolute -top-32 -right-24 h-96 w-96 rounded-full bg-brand/30 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-40 -left-24 h-[28rem] w-[28rem] rounded-full bg-brand-dark/30 blur-3xl"></div>
                <div class="pointer-events-none absolute inset-0 opacity-[0.07]" style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 44px 44px; mask-image: radial-gradient(ellipse at center, #000 30%, transparent 75%);"></div>

                <a href="{{ route('home') }}" class="relative flex items-center gap-3">
                    <img src="{{ asset('assets/icons/alf-sidebar.png') }}" alt="" class="h-10 w-10 object-contain" />
                    <span class="text-sm font-semibold leading-tight">{{ $company }}</span>
                </a>

                <div class="relative max-w-md">
                    <p class="mb-4 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white/80 ring-1 ring-white/15">
                        <span class="h-1.5 w-1.5 rounded-full bg-green-400"></span>
                        {{ __('Internal ERP') }}
                    </p>
                    <h2 class="text-3xl xl:text-4xl font-bold leading-tight">{{ __('Everything the team needs, in one place.') }}</h2>
                    <p class="mt-4 text-sm leading-relaxed text-white/70">{{ __('Training, sales, finance, HR and projects — managed together so nothing falls through the cracks.') }}</p>

                    <ul class="mt-8 grid grid-cols-2 gap-3 text-sm text-white/80">
                        @foreach ([__('Training & certificates'), __('Sales & invoices'), __('Finance & reports'), __('HR & attendance')] as $feature)
                            <li class="flex items-center gap-2">
                                <svg class="h-4 w-4 shrink-0 text-brand-light" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 011.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="relative text-xs text-white/50">&copy; {{ date('Y') }} {{ $company }}</p>
            </aside>

            {{-- Form side --}}
            <main class="relative flex flex-1 flex-col bg-gradient-to-br from-steel-50 via-white to-brand-50/40">
                <div class="flex flex-1 flex-col items-center justify-center px-5 py-10 sm:px-8">
                    <a href="{{ route('home') }}" class="mb-8 lg:hidden">
                        <x-application-logo class="h-14 w-auto" />
                    </a>

                    <div class="w-full max-w-sm auth-enter">
                        {{ $slot }}
                    </div>
                </div>

                <footer class="flex items-center justify-between gap-4 px-5 py-5 text-xs text-steel-500 sm:px-8">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-1 hover:text-brand transition-colors">&larr; {{ __('Back to website') }}</a>
                    <span class="lg:hidden">&copy; {{ date('Y') }} {{ $company }}</span>
                </footer>
            </main>
        </div>
    </body>
</html>
