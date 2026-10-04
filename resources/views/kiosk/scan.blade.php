<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Scan Kehadiran | PT Alfajar Logic Futura</title>
    <meta name="robots" content="noindex">
    <link rel="icon" type="image/png" href="{{ asset('assets/icons/alf.png') }}">
    @vite(['resources/css/app.css'])
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
</head>
<body class="min-h-screen bg-gray-900 font-sans text-white antialiased">
    <div class="mx-auto flex min-h-screen max-w-md flex-col px-4 py-4">
        <header class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <img src="{{ asset('assets/icons/alf.png') }}" alt="" class="h-8 w-8 rounded">
                <span class="text-sm font-semibold">Scan Kehadiran</span>
            </div>
            <a href="{{ route('dashboard') }}" class="text-xs text-gray-400 underline">Kembali ke ERP</a>
        </header>

        <div id="result" class="mt-4 rounded-xl bg-gray-800 px-4 py-4 text-center transition-colors">
            <p id="result-title" class="text-lg font-bold">Arahkan QR Code ID Card ke kamera</p>
            <p id="result-message" class="mt-1 text-sm text-gray-300">Karyawan tercatat masuk/pulang, customer tercatat hadir di training hari ini.</p>
        </div>

        <div class="mt-4 overflow-hidden rounded-xl bg-black">
            <div id="reader" class="w-full"></div>
        </div>
        <p id="camera-error" class="mt-3 hidden rounded-md bg-red-900/60 px-3 py-2 text-sm text-red-100"></p>

        <div class="mt-4">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400">Scan terakhir</h2>
            <ul id="history" class="mt-2 space-y-1.5 text-sm"></ul>
        </div>
    </div>

    <script>
        const scanUrl = @json(route('kiosk.scan'));
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const resultBox = document.getElementById('result');
        const title = document.getElementById('result-title');
        const message = document.getElementById('result-message');
        const history = document.getElementById('history');
        let busy = false;

        const tones = {
            ok: ['bg-green-700', 880],
            warn: ['bg-amber-600', 440],
            fail: ['bg-red-700', 220],
        };

        function beep(freq) {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                osc.frequency.value = freq;
                osc.connect(ctx.destination);
                osc.start();
                setTimeout(() => { osc.stop(); ctx.close(); }, 150);
            } catch (e) {}
            if (navigator.vibrate) navigator.vibrate(120);
        }

        function show(kind, heading, text) {
            resultBox.className = 'mt-4 rounded-xl px-4 py-4 text-center transition-colors ' + tones[kind][0];
            title.textContent = heading;
            message.textContent = text;
            beep(tones[kind][1]);
        }

        function log(kind, text) {
            const li = document.createElement('li');
            li.className = 'rounded-md px-3 py-1.5 ' + (kind === 'ok' ? 'bg-green-900/50' : kind === 'warn' ? 'bg-amber-900/50' : 'bg-red-900/50');
            li.textContent = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' · ' + text;
            history.prepend(li);
            while (history.children.length > 6) history.lastChild.remove();
        }

        async function onScan(text) {
            if (busy) return;
            busy = true;

            try {
                const response = await fetch(scanUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ qr: text }),
                });

                if (response.status === 401 || response.status === 419) {
                    show('fail', 'Sesi berakhir', 'Muat ulang halaman dan login kembali.');
                } else if (!response.ok) {
                    show('fail', 'Gagal', 'QR Code tidak dapat diproses.');
                } else {
                    const data = await response.json();
                    const kind = data.ok ? (data.status === 'duplicate' ? 'warn' : 'ok') : 'fail';
                    show(kind, data.name ? data.name + (data.role ? ' · ' + data.role : '') : (data.ok ? 'Berhasil' : 'Gagal'), data.message);
                    log(kind, (data.name ? data.name + ' — ' : '') + data.message);
                }
            } catch (e) {
                show('fail', 'Tidak ada koneksi', 'Periksa jaringan lalu coba lagi.');
            }

            // Hold the result on screen before accepting the next card.
            setTimeout(() => { busy = false; }, 2500);
        }

        const scanner = new Html5Qrcode('reader');
        scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 240, height: 240 } }, onScan, () => {})
            .catch(() => {
                const box = document.getElementById('camera-error');
                box.textContent = 'Kamera tidak dapat dibuka. Izinkan akses kamera di browser, dan pastikan halaman dibuka lewat HTTPS.';
                box.classList.remove('hidden');
            });
    </script>
</body>
</html>
