<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Verifikasi ID Card | PT Alfajar Logic Futura</title>
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
                <div class="{{ $valid ? 'bg-green-600' : 'bg-red-600' }} px-6 py-6 text-center text-white">
                    <p class="text-xs font-semibold uppercase tracking-wide text-white/80">Verifikasi ID Card</p>
                    <h1 class="mt-1 text-2xl font-bold">{{ $valid ? '✓ ID Card valid' : '✕ ID Card tidak berlaku' }}</h1>
                    <p class="mt-1 text-sm text-white/90">{{ $valid ? 'Kartu ini diterbitkan oleh PT Alfajar Logic Futura.' : 'Kartu ini sudah tidak aktif. Jangan dipercaya sebagai identitas.' }}</p>
                </div>

                <div class="flex flex-col items-center px-6 pt-6">
                    @if ($photoUrl)
                        <img src="{{ $photoUrl }}" alt="{{ $name }}" class="h-32 w-24 rounded-md border border-gray-200 object-cover">
                    @else
                        <div class="flex h-32 w-24 items-center justify-center rounded-md border border-gray-200 bg-gray-100 text-2xl font-bold text-gray-400">{{ $initials }}</div>
                    @endif
                </div>

                <dl class="divide-y divide-gray-100 px-6 py-2 text-sm">
                    <div class="py-3"><dt class="text-xs uppercase tracking-wide text-gray-500">Nama</dt><dd class="mt-0.5 text-base font-semibold text-gray-800">{{ $name }}</dd></div>
                    <div class="py-3"><dt class="text-xs uppercase tracking-wide text-gray-500">Peran</dt><dd class="mt-0.5 font-medium text-gray-800">{{ $role }}</dd></div>
                    <div class="py-3"><dt class="text-xs uppercase tracking-wide text-gray-500">Nomor induk</dt><dd class="mt-0.5 font-medium text-gray-800">{{ $number }}</dd></div>
                    @if ($subtitle)
                        <div class="py-3"><dt class="text-xs uppercase tracking-wide text-gray-500">{{ $role === 'Karyawan' ? 'Jabatan' : 'Keterangan' }}</dt><dd class="mt-0.5 font-medium text-gray-800">{{ $subtitle }}</dd></div>
                    @endif
                    <div class="py-3"><dt class="text-xs uppercase tracking-wide text-gray-500">Status</dt><dd class="mt-0.5 font-medium text-gray-800">{{ $statusLabel }}</dd></div>
                </dl>
            </div>
        </div>
    </body>
</html>
