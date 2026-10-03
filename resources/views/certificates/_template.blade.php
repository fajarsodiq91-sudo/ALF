@php
    /*
     * Uploaded-template certificate: the PNG fills the A4 landscape page and each dynamic field is absolutely
     * positioned over it (x = horizontal centre, y = top, in mm). Sizes are Canva px (96 dpi), i.e. CSS px.
     * $pdf = true makes assets filesystem paths for dompdf; otherwise they are normal URLs for the browser.
     * $data: number, name, statement, date, id_number, qr (svg), signature (path/url or null), signer_name, signer_title.
     */
    $pdf ??= false;
    $embedImages ??= true;
    $pos = $template->positions();
    $font = fn (string $file) => $pdf ? public_path('fonts/certificate/'.$file) : asset('fonts/certificate/'.$file);
    $background = $pdf ? \Illuminate\Support\Facades\Storage::disk('public')->path($template->background_path) : asset('storage/'.$template->background_path);
    $box = fn (string $key) => sprintf('left:%.2fmm;top:%.2fmm;width:%.2fmm;margin-left:-%.2fmm;', $pos[$key]['x'], $pos[$key]['y'], $pos[$key]['w'], $pos[$key]['w'] / 2);
@endphp
<style>
    @font-face { font-family: 'Great Vibes'; src: url('{{ $font('GreatVibes-Regular.ttf') }}') format('truetype'); }
    @font-face { font-family: 'Montserrat'; font-weight: 400; font-style: normal; src: url('{{ $font('Montserrat-Regular.ttf') }}') format('truetype'); }
    @font-face { font-family: 'Montserrat'; font-weight: 700; font-style: normal; src: url('{{ $font('Montserrat-Bold.ttf') }}') format('truetype'); }
    @font-face { font-family: 'Montserrat'; font-weight: 400; font-style: italic; src: url('{{ $font('Montserrat-Italic.ttf') }}') format('truetype'); }
    .cert-t { position: relative; width: 297mm; height: 210mm; overflow: hidden; background: #fff; font-family: 'Montserrat', sans-serif; color: #1c1f24; }
    .cert-t .bg { position: absolute; top: 0; left: 0; width: 297mm; height: 210mm; }
    .cert-t .f { position: absolute; text-align: center; line-height: 1.25; }
    .cert-t .f-name { font-family: 'Great Vibes', cursive; font-size: 64px; line-height: 1.1; }
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
    <div class="f f-name" style="{{ $box('name') }}">{{ $data['name'] }}</div>
    <div class="f f-statement" style="{{ $box('statement') }}">{{ $data['statement'] }}</div>
    <div class="f f-date" style="{{ $box('date') }}">{{ $data['date'] }}</div>
    <div class="f" style="{{ $box('qr') }}height:{{ $pos['qr']['w'] }}mm;">
        <div style="width:{{ $pos['qr']['w'] }}mm;height:{{ $pos['qr']['w'] }}mm;">{!! preg_replace('/<svg /', '<svg style="width:'.$pos['qr']['w'].'mm;height:'.$pos['qr']['w'].'mm" ', $data['qr'], 1) !!}</div>
    </div>
    <div class="f f-id" style="{{ $box('id_number') }}">{{ $data['id_number'] }}</div>
    @if ($data['signature'] && $embedImages)
        <div class="f" style="{{ $box('signature') }}"><img src="{{ $data['signature'] }}" style="max-width:{{ $pos['signature']['w'] }}mm;max-height:22mm;"></div>
    @endif
    <div class="f f-signer-name" style="{{ $box('signer_name') }}">{{ $data['signer_name'] }}</div>
    <div class="f f-signer-title" style="{{ $box('signer_title') }}">{{ $data['signer_title'] }}</div>
</div>
