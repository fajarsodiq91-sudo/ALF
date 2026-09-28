<x-layouts.portal title="Certificate">
    @php
        $session = $certificate->session;
        $period = $session->start_date->isSameDay($session->end_date) ? $session->start_date->format('d F Y') : $session->start_date->format('d F Y').' – '.$session->end_date->format('d F Y');
    @endphp
    <style>@media print { header, .no-print { display: none !important; } body { background: #fff !important; } main { padding: 0 !important; max-width: none !important; } }</style>

    <div class="no-print mb-4 flex items-center justify-between">
        <a href="{{ route('portal.certificates.index') }}" class="text-sm font-medium text-brand hover:text-brand-dark">&larr; All certificates</a>
        <button type="button" onclick="window.print()" class="rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-3 py-1.5 text-sm font-medium text-white shadow-sm">Print / Save as PDF</button>
    </div>

    <div class="mx-auto max-w-3xl border-8 border-double border-brand bg-white p-10 text-center shadow-lg">
        <img src="{{ asset('assets/icons/alf.png') }}" alt="" class="mx-auto h-14 w-14">
        <p class="mt-2 text-sm font-semibold uppercase tracking-widest text-steel-800">PT Alfajar Logic Futura</p>
        <h1 class="mt-6 text-3xl font-bold uppercase tracking-wide text-gray-900">Certificate of Completion</h1>
        <p class="mt-6 text-sm text-gray-500">This certifies that</p>
        <p class="mt-2 text-3xl font-semibold text-brand-dark">{{ $certificate->customer->name }}</p>
        <p class="mt-6 text-sm text-gray-500">has successfully completed the learning program</p>
        <p class="mt-2 text-xl font-semibold text-gray-900">{{ $session->program->name }}</p>
        <p class="mt-2 text-sm text-gray-500">{{ $period }} · {{ $session->meetings->count() }} {{ \Illuminate\Support\Str::plural('meeting', $session->meetings->count()) }}</p>

        <div class="mt-10 flex items-end justify-between text-left text-xs text-gray-500">
            <div>
                <p>Certificate number</p>
                <p class="font-mono text-sm font-semibold text-gray-800">{{ $certificate->number }}</p>
            </div>
            <div class="text-right">
                <p>Issued on {{ $certificate->issued_at->format('d F Y') }}</p>
                @if ($session->instructor)<p class="mt-1">Instructor: {{ $session->instructor->name }}</p>@endif
            </div>
        </div>
    </div>
</x-layouts.portal>
