<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>ID Card</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/icons/alf.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @page { size: 54mm 85.6mm; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e5e7eb; font-family: 'Poppins', 'Helvetica Neue', Arial, sans-serif; color: #1f2937; }
        .toolbar { text-align: center; padding: 16px; }
        .toolbar button { background: #8b0000; color: #fff; border: 0; border-radius: 6px; padding: 8px 18px; font-size: 14px; cursor: pointer; }
        .sheet { display: flex; gap: 24px; justify-content: center; flex-wrap: wrap; padding: 0 16px 32px; }
        .card { width: 54mm; height: 85.6mm; background: #fff; border-radius: 3mm; overflow: hidden; display: flex; flex-direction: column; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,.2); position: relative; }
        .card::before { content: ''; position: absolute; inset: 0 0 auto 0; height: 2.5mm; background: var(--accent); }
        .front { padding: 7mm 4mm 4mm; text-align: center; }
        .logo { width: 44mm; height: auto; }
        .role { margin-top: 3mm; font-size: 8pt; font-weight: 600; color: var(--accent); line-height: 1.3; }
        .photo { margin-top: 4mm; width: 28mm; height: 36mm; border-radius: 2mm; border: .4mm solid #d1d5db; object-fit: cover; background: #f3f4f6; display: flex; align-items: center; justify-content: center; font-size: 22pt; font-weight: 700; color: #9ca3af; }
        .number { margin-top: 3.5mm; font-size: 10pt; font-weight: 500; letter-spacing: .6pt; color: #6b7280; line-height: 1.1; }
        .name { margin-top: .4mm; font-size: 11pt; font-weight: 700; line-height: 1.15; }
        .subtitle { margin-top: .8mm; font-size: 7.5pt; color: #6b7280; line-height: 1.2; }
        .back { justify-content: center; padding: 4mm; text-align: center; }
        .qr { width: 40mm; height: 40mm; }
        .qr svg { width: 100%; height: 100%; }
        .back .logo { width: 30mm; margin-top: 8mm; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { display: block; padding: 0; }
            .card { box-shadow: none; border-radius: 0; page-break-after: always; break-after: page; }
            .card:last-child { page-break-after: auto; break-after: auto; }
            
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="toolbar"><button onclick="window.print()">Cetak ID Card</button></div>
    <div class="sheet">
        @foreach ($cards as $card)
            <div class="card front" style="--accent: {{ $card['accent'] }}">
                <img class="logo" src="{{ asset('assets/images/company-logo.png') }}" alt="Alfajar Creative to be Excellent">
                <div class="role">{{ $card['role'] }}<br>PT Alfajar Logic Futura</div>
                @if ($card['photoUrl'])
                    <img class="photo" src="{{ $card['photoUrl'] }}" alt="{{ $card['name'] }}">
                @else
                    <div class="photo">{{ $card['initials'] }}</div>
                @endif
                <div class="number">{{ $card['number'] }}</div>
                <div class="name">{{ $card['name'] }}</div>
                @if ($card['subtitle'])
                    <div class="subtitle">{{ $card['subtitle'] }}</div>
                @endif
            </div>
            <div class="card back" style="--accent: {{ $card['accent'] }}">
                <div class="qr">{!! $card['qr'] !!}</div>
                <img class="logo" src="{{ asset('assets/images/company-logo.png') }}" alt="">
            </div>
        @endforeach
    </div>
</body>
</html>
