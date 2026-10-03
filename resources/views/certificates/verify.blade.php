<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Certificate verification | PT Alfajar Logic Futura</title>
        <meta name="robots" content="noindex">
        <link rel="icon" type="image/png" href="{{ asset('assets/icons/alf.png') }}" />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css'])
    </head>
    <body class="font-sans antialiased text-gray-900 min-h-screen bg-gradient-to-br from-steel-100 via-white to-brand-50">
        <div class="mx-auto max-w-lg px-4 py-8 sm:py-12">
            <div class="mb-6 flex items-center justify-center gap-3">
                <img src="{{ asset('assets/icons/alf.png') }}" alt="" class="h-10 w-10 rounded">
                <span class="text-base font-semibold text-steel-800">PT Alfajar Logic Futura</span>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl">
                <div class="bg-green-600 px-6 py-6 text-center text-white">
                    <p class="text-xs font-semibold uppercase tracking-wide text-white/80">Certificate verification</p>
                    <h1 class="mt-1 text-2xl font-bold">&#10003; Valid certificate</h1>
                    <p class="mt-1 text-sm text-white/90">This certificate was issued by PT Alfajar Logic Futura.</p>
                </div>

                <dl class="divide-y divide-gray-100 px-6 py-2 text-sm">
                    <div class="py-3"><dt class="text-xs uppercase tracking-wide text-gray-500">Awarded to</dt><dd class="mt-0.5 text-base font-semibold text-gray-800">{{ $certificate->customer->name }}</dd></div>
                    <div class="py-3"><dt class="text-xs uppercase tracking-wide text-gray-500">Program</dt><dd class="mt-0.5 font-medium text-gray-800">{{ $certificate->session->program->name }}</dd></div>
                    <div class="py-3"><dt class="text-xs uppercase tracking-wide text-gray-500">Certificate number</dt><dd class="mt-0.5 font-medium text-gray-800">{{ $certificate->number }}</dd></div>
                    <div class="py-3"><dt class="text-xs uppercase tracking-wide text-gray-500">ID number</dt><dd class="mt-0.5 font-medium text-gray-800">{{ $certificate->customer->customer_code }}</dd></div>
                    <div class="py-3"><dt class="text-xs uppercase tracking-wide text-gray-500">Issued on</dt><dd class="mt-0.5 font-medium text-gray-800">{{ $certificate->issued_at->format('d F Y') }}</dd></div>
                </dl>

                <div class="border-t border-gray-100 px-6 py-4 text-center">
                    <a href="{{ route('customer-portfolio.show', $certificate->customer->portfolioToken()) }}" class="text-sm font-medium text-brand hover:text-brand-dark">View {{ explode(' ', trim($certificate->customer->name))[0] }}'s portfolio &rarr;</a>
                </div>
            </div>
        </div>
    </body>
</html>
