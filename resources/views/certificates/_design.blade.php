@php
    // $logoSrc lets the PDF renderer pass a filesystem path (faster, no self-fetch over HTTP)
    // while the on-screen preview keeps using a normal asset URL.
    $logoSrc ??= asset('assets/icons/alf.png');
    // dompdf needs the GD extension to embed PNG/JPG images; without it, degrade gracefully to text
    // instead of failing the whole PDF. Browsers never have this problem, so they always embed images.
    $embedImages ??= true;

    $session = $certificate->session;
    $period = $session->start_date->isSameDay($session->end_date)
        ? $session->start_date->format('d F Y')
        : $session->start_date->format('d F Y').' – '.$session->end_date->format('d F Y');
    $meetingCount = $session->meetings->count();
    $instructor = $session->instructor;
    $companyName = \App\Models\Setting::get('company_name', 'PT Alfajar Logic Futura');
    $signerName = $instructor?->name ?? \App\Models\Setting::get('certificate_signer_name', $companyName);
    $signerTitle = $instructor?->position ?? \App\Models\Setting::get('certificate_signer_title', 'Training Team');
    $signatureUrl = $instructor?->signatureUrl();
    $portfolioUrl = route('customer-portfolio.show', $certificate->customer->portfolioToken());
    $qr = \App\Services\QrCodeGenerator::svg($portfolioUrl, 200, true);
    $firstName = explode(' ', trim($certificate->customer->name))[0];
@endphp
<style>
    .cert-wrap { font-family: 'PT Serif', 'Times New Roman', serif; color: #1c1f24; }
    .cert { position: relative; width: 297mm; height: 210mm; margin: 0 auto; background: #fff; overflow: hidden; box-sizing: border-box; }
    .cert__frame { position: absolute; inset: 6mm; border: 0.6mm solid #d9b45c; }
    .cert__bg { position: absolute; top: 0; left: 0; width: 297mm; height: 210mm; }
    .cert__content { position: relative; z-index: 1; padding: 16mm 20mm; height: 210mm; box-sizing: border-box; }
    .cert__brand { position: absolute; top: 12mm; right: 18mm; text-align: right; z-index: 2; }
    .cert__brand img { height: 14mm; }
    .cert__brand .name { font-family: 'Figtree', Arial, sans-serif; font-weight: 700; font-size: 5mm; letter-spacing: 0.5mm; color: #b00000; margin-top: 1mm; }
    .cert__brand .tagline { font-family: 'Figtree', Arial, sans-serif; font-size: 2.6mm; color: #6b7280; letter-spacing: 0.3mm; }
    .cert__title { text-align: center; margin-top: 6mm; }
    .cert__title h1 { margin: 0; font-size: 15mm; font-weight: 700; letter-spacing: 1mm; text-transform: uppercase; }
    .cert__title p { margin: 1mm 0 0; font-size: 6mm; font-style: italic; color: #3a3f47; }
    .cert__number { display: inline-block; margin: 3mm auto 0; padding: 1.5mm 6mm; border: 0.3mm solid #b00000; border-radius: 20mm; font-family: 'Figtree', Arial, sans-serif; font-size: 3.2mm; font-weight: 600; letter-spacing: 0.3mm; color: #8a0000; }
    .cert__center { text-align: center; margin-top: 8mm; }
    .cert__center .lede { font-size: 3.6mm; color: #6b7280; }
    .cert__center .name { font-family: 'Alex Brush', 'Brush Script MT', cursive; font-size: 18mm; color: #8a0000; margin: 2mm 0; line-height: 1; }
    .cert__center .body { max-width: 190mm; margin: 3mm auto 0; font-size: 4mm; line-height: 1.6; color: #292d33; }
    .cert__center .program { font-weight: 700; }
    .cert__center .period { margin-top: 2mm; font-size: 3.4mm; color: #6b7280; }
    .cert__footer { position: absolute; left: 20mm; right: 20mm; bottom: 16mm; display: flex; align-items: flex-end; justify-content: space-between; }
    .cert__qr { text-align: center; }
    .cert__qr .box { position: relative; width: 26mm; height: 26mm; }
    .cert__qr .box svg { width: 26mm; height: 26mm; }
    .cert__qr .mark { position: absolute; top: 50%; left: 50%; width: 8mm; height: 8mm; margin: -4mm 0 0 -4mm; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    .cert__qr .mark img { width: 6mm; height: 6mm; }
    .cert__qr .caption { margin-top: 1.5mm; font-family: 'Figtree', Arial, sans-serif; font-size: 2.4mm; color: #6b7280; max-width: 30mm; }
    .cert__meta { text-align: left; font-family: 'Figtree', Arial, sans-serif; font-size: 2.8mm; color: #6b7280; }
    .cert__meta .label { text-transform: uppercase; letter-spacing: 0.3mm; font-size: 2.3mm; }
    .cert__meta .value { font-weight: 600; color: #292d33; }
    .cert__signature { text-align: center; width: 55mm; }
    .cert__signature .sig-img { height: 16mm; object-fit: contain; }
    .cert__signature .sig-script { font-family: 'Alex Brush', 'Brush Script MT', cursive; font-size: 11mm; color: #1c1f24; height: 16mm; line-height: 16mm; }
    .cert__signature .line { border-top: 0.3mm solid #9ca3af; margin-top: 1mm; padding-top: 1.5mm; }
    .cert__signature .signer-name { font-family: 'Figtree', Arial, sans-serif; font-weight: 700; font-size: 3.4mm; color: #1c1f24; }
    .cert__signature .signer-title { font-family: 'Figtree', Arial, sans-serif; font-size: 2.8mm; color: #6b7280; }
</style>

<div class="cert-wrap">
    <div class="cert">
        <svg class="cert__bg" viewBox="0 0 297 210" preserveAspectRatio="none">
            <polygon points="0,0 120,0 0,85" fill="#8a0000" />
            <polygon points="0,0 106,0 0,74" fill="#ffffff" />
            <polygon points="0,0 98,0 0,68" fill="#d9b45c" />
            <polygon points="0,0 90,0 0,62" fill="#ffffff" />
            <polygon points="0,0 58,0 0,40" fill="#8c949e" />
            <polygon points="0,0 48,0 0,33" fill="#ffffff" />
            <polygon points="0,0 20,0 0,14" fill="#b00000" />

            <polygon points="297,210 177,210 297,125" fill="#8c949e" />
            <polygon points="297,210 191,210 297,136" fill="#ffffff" />
            <polygon points="297,210 199,210 297,142" fill="#8a0000" />
            <polygon points="297,210 207,210 297,148" fill="#ffffff" />
            <polygon points="297,210 239,210 297,170" fill="#d9b45c" />
            <polygon points="297,210 249,210 297,177" fill="#ffffff" />
            <polygon points="297,210 277,210 297,196" fill="#b00000" />
        </svg>

        <div class="cert__frame"></div>

        <div class="cert__brand">
            @if ($embedImages)
                <img src="{{ $logoSrc }}">
            @endif
            <div class="name">ALFAJAR</div>
            <div class="tagline">CREATIVE TO BE EXCELLENT</div>
        </div>

        <div class="cert__content">
            <div class="cert__title">
                <h1>Certificate</h1>
                <p>of Completion</p>
                <div class="cert__number">{{ $certificate->number }}</div>
            </div>

            <div class="cert__center">
                <p class="lede">Proudly presented to</p>
                <p class="name">{{ $certificate->customer->name }}</p>
                <p class="body">
                    has completed the requirements to pass the
                    <span class="program">{{ $session->program->name }}</span>
                    program held by {{ $companyName }}.
                </p>
                <p class="period">{{ $period }} · {{ $meetingCount }} {{ $meetingCount === 1 ? 'meeting' : 'meetings' }}</p>
            </div>

            <div class="cert__footer">
                <div class="cert__qr">
                    <div class="box">
                        {!! $qr !!}
                        @if ($embedImages)
                            <div class="mark"><img src="{{ $logoSrc }}"></div>
                        @endif
                    </div>
                    <p class="caption">Scan to view {{ $firstName }}'s portfolio</p>
                </div>

                <div class="cert__meta">
                    <p class="label">Issued on</p>
                    <p class="value">{{ $certificate->issued_at->format('d F Y') }}</p>
                </div>

                <div class="cert__signature">
                    @if ($signatureUrl && $embedImages)
                        <img class="sig-img" src="{{ $signatureUrl }}">
                    @else
                        <div class="sig-script">{{ $signerName }}</div>
                    @endif
                    <div class="line">
                        <div class="signer-name">{{ $signerName }}</div>
                        <div class="signer-title">{{ $signerTitle }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
