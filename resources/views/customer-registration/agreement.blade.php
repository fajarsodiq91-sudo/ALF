<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Agreement | PT Alfajar Logic Futura</title>
        <link rel="icon" type="image/png" href="{{ asset('assets/icons/alf.png') }}" />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 min-h-screen bg-gradient-to-br from-steel-100 via-white to-brand-50">
        <div class="mx-auto max-w-2xl px-4 py-8">
            <div class="mb-6 flex items-center justify-center gap-3">
                <img src="{{ asset('assets/icons/alf.png') }}" alt="" class="h-10 w-10 rounded">
                <span class="text-base font-semibold text-steel-800">PT Alfajar Logic Futura</span>
            </div>
            <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-6">
                <h1 class="text-lg font-semibold text-gray-800">Agreement</h1>
                <div class="mt-4 whitespace-pre-line text-sm leading-relaxed text-gray-700">{{ $content }}</div>
            </div>
        </div>
    </body>
</html>
