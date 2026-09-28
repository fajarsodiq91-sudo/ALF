<x-layouts.portal title="My Certificates">
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold text-gray-800">My certificates</h1>
            <a href="{{ route('portal.dashboard') }}" class="text-sm font-medium text-brand hover:text-brand-dark">&larr; Back to my programs</a>
        </div>

        <div class="bg-white rounded-lg shadow-md border border-gray-200 divide-y divide-gray-100">
            @forelse ($certificates as $certificate)
                <div class="flex flex-wrap items-center justify-between gap-3 p-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ $certificate->session->program->name }}</p>
                        <p class="font-mono text-xs text-gray-500">{{ $certificate->number }} · issued {{ $certificate->issued_at->format('d M Y') }}</p>
                    </div>
                    <a href="{{ route('portal.certificates.show', $certificate) }}" class="rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:shadow-md transition">View certificate</a>
                </div>
            @empty
                <p class="p-8 text-center text-sm text-gray-500">No certificate yet. It appears here once your learning session is completed.</p>
            @endforelse
        </div>
    </div>
</x-layouts.portal>
