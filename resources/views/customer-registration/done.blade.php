<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Thank you | PT Alfajar Logic Futura</title>
        <link rel="icon" type="image/png" href="{{ asset('assets/icons/alf.png') }}" />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 min-h-screen bg-gradient-to-br from-steel-100 via-white to-brand-50">
        <div class="mx-auto max-w-xl px-4 py-8 sm:py-12">
            <div class="mb-6 flex items-center justify-center gap-3">
                <img src="{{ asset('assets/icons/alf.png') }}" alt="" class="h-10 w-10 rounded">
                <span class="text-base font-semibold text-steel-800">PT Alfajar Logic Futura</span>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl">
                {{-- Hero --}}
                <div class="bg-gradient-to-br from-steel-900 via-brand-dark to-brand-light px-6 py-9 text-center text-white">
                    <div class="relative mx-auto flex h-16 w-16 items-center justify-center">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white/30"></span>
                        <span class="relative flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                    </div>
                    <h1 class="mt-5 text-2xl font-bold">Thank you{{ $registered ? ', '.$registered['name'] : '' }}!</h1>
                    <p class="mt-1 text-sm text-white/80">Your registration has been received.</p>
                    <span class="mt-4 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-medium ring-1 ring-white/30">
                        <span class="h-2 w-2 rounded-full bg-amber-300"></span> Waiting for approval
                    </span>
                </div>

                <div class="px-6 py-6">
                    <h2 class="text-sm font-semibold text-gray-800">What happens next</h2>

                    <ol class="mt-4 space-y-0">
                        <li class="relative flex gap-4 pb-6">
                            <span class="absolute left-4 top-8 -ml-px h-full w-0.5 bg-brand/30"></span>
                            <span class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-light to-brand-dark text-white shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            </span>
                            <div>
                                <p class="text-sm font-medium text-gray-800">Registration received</p>
                                <p class="text-xs text-gray-500">Your details and photo were saved.</p>
                            </div>
                        </li>
                        <li class="relative flex gap-4 pb-6">
                            <span class="absolute left-4 top-8 -ml-px h-full w-0.5 bg-gray-200"></span>
                            <span class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 border-amber-400 bg-amber-50 text-amber-600">
                                <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-amber-500"></span>
                            </span>
                            <div>
                                <p class="text-sm font-medium text-gray-800">Our team reviews your registration</p>
                                <p class="text-xs text-gray-500">We will prepare your program and meeting schedule. You are here.</p>
                            </div>
                        </li>
                        <li class="relative flex gap-4">
                            <span class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 border-gray-200 bg-white text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            </span>
                            <div>
                                <p class="text-sm font-medium text-gray-800">You get an approval email</p>
                                <p class="text-xs text-gray-500">It contains your customer ID, your program schedule, and a link to log in to your customer portal.</p>
                            </div>
                        </li>
                    </ol>

                    @if (! empty($registered['programs']))
                        <div class="mt-6 rounded-lg border border-gray-200 px-4 py-3">
                            <h3 class="text-sm font-semibold text-gray-800">Programs and dates you asked for</h3>
                            <p class="text-xs text-gray-500">Our team will confirm the final schedule when they approve your registration.</p>
                            <ul class="mt-2 space-y-2 text-sm">
                                @foreach ($registered['programs'] as $entry)
                                    <li>
                                        <span class="font-medium text-gray-800">{{ $entry['name'] }}</span>
                                        <ul class="mt-0.5 list-disc pl-5 text-gray-500">
                                            @foreach ($entry['meetings'] as $label)<li>{{ $label }}</li>@endforeach
                                        </ul>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($registered)
                        <div class="mt-6 flex gap-3 rounded-lg bg-brand-50 px-4 py-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            <p class="text-sm text-gray-700">
                                A confirmation email was sent to <span class="font-semibold">{{ $registered['email'] }}</span>.
                                <span class="block text-xs text-gray-500">Please also check your spam folder.</span>
                            </p>
                        </div>
                    @endif
                </div>

                <div class="border-t border-gray-100 bg-gray-50 px-6 py-4 text-center">
                    <p class="text-xs text-gray-500">Questions? We are happy to help.</p>
                    <div class="mt-2 flex flex-wrap items-center justify-center gap-x-5 gap-y-1 text-sm font-medium">
                        <a href="mailto:admin@alfajarlogic.com" class="text-brand hover:text-brand-dark">admin@alfajarlogic.com</a>
                        <a href="https://wa.me/6282125298452" target="_blank" rel="noopener noreferrer" class="text-brand hover:text-brand-dark">WhatsApp</a>
                        <a href="{{ route('home') }}" class="text-gray-500 hover:text-gray-700">Visit our website</a>
                    </div>
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-gray-400">&copy; {{ date('Y') }} PT Alfajar Logic Futura</p>
        </div>
    </body>
</html>
