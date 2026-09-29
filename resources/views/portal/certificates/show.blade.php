<x-layouts.portal title="Certificate">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=pt-serif:400,700i|alex-brush&display=swap" rel="stylesheet" />

    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('portal.certificates.index') }}" class="text-sm font-medium text-brand hover:text-brand-dark">&larr; All certificates</a>
        <a href="{{ route('portal.certificates.download', $certificate) }}" class="rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:shadow-md transition">Download PDF</a>
    </div>

    <div class="-mx-4 overflow-x-auto rounded-lg bg-steel-100 p-4 sm:mx-0 sm:p-6">
        <div class="mx-auto shadow-lg" style="width: 297mm;">
            @include('certificates._design', ['certificate' => $certificate])
        </div>
    </div>
</x-layouts.portal>
