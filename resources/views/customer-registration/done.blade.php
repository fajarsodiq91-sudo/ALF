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
        <div class="mx-auto max-w-xl px-4 py-8">
            <div class="mb-6 flex items-center justify-center gap-3">
                <img src="{{ asset('assets/icons/alf.png') }}" alt="" class="h-10 w-10 rounded">
                <span class="text-base font-semibold text-steel-800">PT Alfajar Logic Futura</span>
            </div>
            <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-6 text-center">
                <h1 class="text-lg font-semibold text-gray-800">Thank you{{ $registered ? ', '.$registered['name'] : '' }}!</h1>
                <p class="mt-2 text-sm text-gray-500">Your details have been received.</p>
                @if ($registered)
                    <div class="mt-5 rounded-lg bg-brand-50 px-4 py-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Your customer ID</p>
                        <p class="mt-1 font-mono text-3xl font-semibold text-brand">{{ $registered['code'] }}</p>
                        <p class="mt-2 text-xs text-gray-500">Please keep this ID and mention it on your next order.</p>
                    </div>
                @endif
            </div>
        </div>
    </body>
</html>
