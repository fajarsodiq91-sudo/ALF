<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Template preview — {{ $template->name }}</title>
    <style>body { margin: 0; background: #e5e7eb; } .page { width: 297mm; margin: 10mm auto; box-shadow: 0 2px 12px rgba(0,0,0,.25); }</style>
</head>
<body>
    <div class="page">
        @include('certificates._template', [
            'template' => $template,
            'data' => [
                'number' => 'ALF/LRN/2026/X/001-A1P',
                'name' => 'Nama Penerima Sertifikat',
                'statement' => 'has completed the requirements to pass the Contoh Program Pelatihan program held by PT Alfajar Logic Futura.',
                'date' => now()->format('d F Y'),
                'id_number' => '261001',
                'verify_url' => url('/'),
                'qr' => \App\Services\QrCodeGenerator::svg(url('/'), 400),
                'signature' => null,
                'signer_name' => 'Nama Penandatangan',
                'signer_title' => 'Instructor',
            ],
        ])
    </div>
</body>
</html>
