@php
    /*
     * Uploaded-template certificate: the PNG fills the A4 landscape page and each dynamic field is absolutely
     * positioned over it (x = horizontal centre, y = top, in mm). Sizes are Canva px (96 dpi), i.e. CSS px.
     * $pdf = true makes assets filesystem paths for dompdf; otherwise they are normal URLs for the browser.
     * The name's y is where a one-line name sits: longer names keep their last line there and grow upward.
     * $data: number, name, statement, date, id_number, qr (svg), signature (path/url or null), signer_name, signer_title.
     */
    $pdf ??= false;
    $embedImages ??= true;
    $pos = $template->positions();
    // dompdf reads JPEG natively but needs the GD extension to decode PNG; never hand back a silently blank certificate.
    $isJpeg = in_array(strtolower(pathinfo($template->background_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg']);
    throw_if($pdf && ! $isJpeg && ! extension_loaded('gd'), \RuntimeException::class, 'The PHP GD extension is required to put a PNG template in a PDF. Enable GD on the server or upload the template as JPG.');
    $embedImages = $pdf ? true : $embedImages;
    $font = fn (string $file) => $pdf ? public_path('fonts/certificate/'.$file) : asset('fonts/certificate/'.$file);
    $background = $pdf ? \Illuminate\Support\Facades\Storage::disk('public')->path($template->background_path) : asset('storage/'.$template->background_path);
    // dompdf sets text lower inside its line box than a browser does (more so for bigger fonts), so the PDF lifts
    // each text field by the measured offset (CSS px) to land where the browser preview shows it.
    $pdfLift = ['number' => 7, 'name' => 30, 'statement' => 9, 'date' => 4, 'id_number' => 5, 'signer_name' => 4, 'signer_title' => 5];
    $box = fn (string $key, float $raise = 0) => sprintf('left:%.2fmm;top:%.2fmm;width:%.2fmm;margin-left:-%.2fmm;', $pos[$key]['x'], $pos[$key]['y'] - $raise - ($pdf ? ($pdfLift[$key] ?? 0) * 25.4 / 96 : 0), $pos[$key]['w'], $pos[$key]['w'] / 2);
    $nameLines = \App\Services\CertificateNameLines::split((string) $data['name'], $pos['name']['w']);
@endphp
<style>
    @font-face { font-family: 'Great Vibes'; src: url('{{ $font('GreatVibes-Regular.ttf') }}') format('truetype'); }
    @font-face { font-family: 'Montserrat'; font-weight: 400; font-style: normal; src: url('{{ $font('Montserrat-Regular.ttf') }}') format('truetype'); }
    @font-face { font-family: 'Montserrat'; font-weight: 700; font-style: normal; src: url('{{ $font('Montserrat-Bold.ttf') }}') format('truetype'); }
    @font-face { font-family: 'Montserrat'; font-weight: 400; font-style: italic; src: url('{{ $font('Montserrat-Italic.ttf') }}') format('truetype'); }
    .cert-t { position: relative; width: 297mm; height: 210mm; overflow: hidden; background: #fff; font-family: 'Montserrat', sans-serif; color: #1c1f24; }
    .cert-t .bg { position: absolute; top: 0; left: 0; width: 297mm; height: 210mm; }
    .cert-t .f { position: absolute; text-align: center; line-height: 1.25; }
    .cert-t .f-name { font-family: 'Great Vibes', cursive; font-size: {{ \App\Services\CertificateNameLines::FONT_SIZE_PX }}px; line-height: 1.1; white-space: nowrap; }
    .cert-t .f-number { font-size: 14px; font-weight: 700; }
    .cert-t .f-statement { font-size: 14px; font-style: italic; line-height: 1.5; }
    .cert-t .f-date, .cert-t .f-id, .cert-t .f-signer-name, .cert-t .f-signer-title { font-size: 13px; }
    .cert-t .f-signer-name { font-weight: 700; }
</style>
<div class="cert-t">
    @if ($embedImages)
        <img class="bg" src="{{ $background }}">
    @endif
    <div class="f f-number" style="{{ $box('number') }}">{{ $data['number'] }}</div>
    @foreach ($nameLines as $line)
        <div class="f f-name" style="{{ $box('name', $loop->remaining * \App\Services\CertificateNameLines::LINE_PITCH_MM) }}">{{ $line }}</div>
    @endforeach
    <div class="f f-statement" style="{{ $box('statement') }}">{{ $data['statement'] }}</div>
    <div class="f f-date" style="{{ $box('date') }}">{{ $data['date'] }}</div>
    <div class="f" style="{{ $box('qr') }}height:{{ $pos['qr']['w'] }}mm;">
        <div style="width:{{ $pos['qr']['w'] }}mm;height:{{ $pos['qr']['w'] }}mm;"><a href="{{ $data['verify_url'] }}" style="display:block;text-decoration:none;"><img src="data:image/svg+xml;base64,{{ base64_encode($data['qr']) }}" style="width:{{ $pos['qr']['w'] }}mm;height:{{ $pos['qr']['w'] }}mm;"></a></div>
    </div>
    <div class="f f-id" style="{{ $box('id_number') }}">{{ $data['id_number'] }}</div>
    @if ($data['signature'] && $embedImages)
        <div class="f" style="{{ $box('signature') }}"><img src="{{ $data['signature'] }}" style="max-width:{{ $pos['signature']['w'] }}mm;max-height:22mm;"></div>
    @endif
    <div class="f f-signer-name" style="{{ $box('signer_name') }}">{{ $data['signer_name'] }}</div>
    <div class="f f-signer-title" style="{{ $box('signer_title') }}">{{ $data['signer_title'] }}</div>
</div>
