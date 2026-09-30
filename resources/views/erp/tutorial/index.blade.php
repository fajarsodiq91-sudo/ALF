<x-layouts.erp title="Tutorial">
    @php
        // The end-to-end flow of the business. Each step points to the topic that explains it.
        $flow = [
            ['Siapkan data dasar: program training, akun kas/bank beserta kategorinya, dan jam operasional.', 'training-programs'],
            ['Undang customer: pilih tipe customer, lalu bagikan QR code atau tautan pendaftarannya.', 'sales-customers'],
            ['Customer mengisi data diri, memilih program, dan memilih jadwal pertemuan sendiri.', 'sales-customers'],
            ['Tim meninjau pendaftaran lalu menyetujuinya. Customer mendapat ID, sesi training dibuat, dan email berisi tautan login terkirim.', 'sales-customers'],
            ['Sesi berjalan: tandai pertemuan yang sudah terlaksana, tangani permintaan reschedule, dan konfirmasi pembayaran. Pembayaran otomatis tercatat sebagai pemasukan di Finance.', 'training-sessions'],
            ['Setelah sesi selesai, tekan Mark as done. Sertifikat untuk program bertipe Learning terbit otomatis.', 'training-sessions'],
            ['Customer mengunggah proyek hasil belajarnya di portal; tim menandainya untuk portofolio.', 'customer-portal'],
        ];
    @endphp

    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            @include('erp.tutorial._nav', ['active' => null])

            <div class="lg:col-span-3 space-y-4">
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-800">Panduan menggunakan ERP</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Pilih topik di menu sebelah kiri atau kartu di bawah ini. Anda hanya melihat topik untuk modul yang boleh Anda akses;
                        jika modul tertentu tidak muncul, hak aksesnya belum diberikan. Hubungi Super Admin untuk mengubah role Anda.
                    </p>
                    <p class="mt-2 text-sm text-gray-600">Baru pertama kali? Mulailah dari <a href="{{ route('tutorial.show', 'getting-started') }}" class="font-medium text-brand hover:text-brand-dark">Memulai</a>.</p>
                </div>

                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h2 class="text-base font-semibold text-gray-800">Alur kerja dari awal sampai akhir</h2>
                    <p class="mt-1 text-xs text-gray-500">Gambaran besar bagaimana customer diproses. Klik langkah untuk membuka penjelasannya.</p>
                    <ol class="mt-4 list-decimal space-y-2 pl-5 text-sm text-gray-600 marker:font-semibold marker:text-brand">
                        @foreach ($flow as [$text, $slug])
                            <li>
                                @if (\App\Services\Tutorial::canView($slug, auth()->user()))
                                    <a href="{{ route('tutorial.show', $slug) }}" class="hover:text-brand">{{ $text }}</a>
                                @else
                                    {{ $text }}
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>

                @foreach ($groups as $group => $topics)
                    <div>
                        <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $group }}</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach ($topics as $slug => $topic)
                                <a href="{{ route('tutorial.show', $slug) }}" class="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:border-brand/40 hover:shadow-md">
                                    <span class="block text-sm font-semibold text-gray-800">{{ $topic['title'] }}</span>
                                    <span class="mt-1 block text-xs text-gray-500">{{ $topic['summary'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.erp>
