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
                <div class="px-6 py-8 text-center">
                    <h1 class="text-lg font-semibold text-gray-800">Thank you for registering</h1>
                    <p class="mt-2 text-sm text-gray-500">
                        Check your email for a confirmation. It has a link to see the status of your registration, including when it is approved.
                    </p>
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
