<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>ID Card tidak ditemukan | PT Alfajar Logic Futura</title>
        <meta name="robots" content="noindex">
        <link rel="icon" type="image/png" href="{{ asset('assets/icons/alf.png') }}" />
        @vite(['resources/css/app.css'])
    </head>
    <body class="font-sans antialiased text-gray-900 min-h-screen bg-gradient-to-br from-steel-100 via-white to-brand-50 flex items-center justify-center px-4">
        <div class="max-w-sm rounded-xl border border-gray-200 bg-white p-8 text-center shadow-xl">
            <img src="{{ asset('assets/icons/alf.png') }}" alt="" class="mx-auto h-10 w-10 rounded">
            <h1 class="mt-4 text-lg font-semibold text-red-700">ID Card tidak ditemukan</h1>
            <p class="mt-2 text-sm text-gray-500">Kartu ini tidak dapat diverifikasi. Periksa kembali QR Code yang Anda pindai.</p>
        </div>
    </body>
</html>
