@php
    // Uploaded template when one applies (program's own, else the default); otherwise the built-in design.
    $embedImages ??= true;
    $pdf ??= false;
    $session = $certificate->session;
    $template = \App\Models\CertificateTemplate::forProgram($session->program);
@endphp
@if ($template)
    @php
        $companyName = \App\Models\Setting::get('company_name', 'PT Alfajar Logic Futura');
        $instructor = $session->instructor;
        $signature = $instructor?->signature_path;
    @endphp
    @include('certificates._template', [
        'template' => $template,
        'pdf' => $pdf,
        'embedImages' => $embedImages,
        'data' => [
            'number' => $certificate->number,
            'name' => $certificate->customer->name,
            'statement' => 'has completed the requirements to pass the '.$session->program->name.' program held by '.$companyName.'.',
            'date' => $certificate->issued_at->format('d F Y'),
            'id_number' => $certificate->customer->customer_code,
            'qr' => \App\Services\QrCodeGenerator::svg(route('customer-portfolio.show', $certificate->customer->portfolioToken()), 400),
            'signature' => $signature ? ($pdf ? \Illuminate\Support\Facades\Storage::disk('public')->path($signature) : $instructor->signatureUrl()) : null,
            'signer_name' => $instructor?->name ?? \App\Models\Setting::get('certificate_signer_name', $companyName),
            'signer_title' => $instructor?->position ?? \App\Models\Setting::get('certificate_signer_title', 'Training Team'),
        ],
    ])
@else
    @include('certificates._legacy', get_defined_vars())
@endif
