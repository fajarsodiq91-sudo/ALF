<x-layouts.erp :title="$title">
    <div class="max-w-3xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
            <span class="inline-block rounded-full bg-brand-50 text-brand px-3 py-1 text-xs font-semibold uppercase tracking-wide mb-4">
                Coming Soon
            </span>
            <h2 class="text-xl font-semibold text-gray-800 mb-2">{{ $title }}</h2>
            <p class="text-gray-500">
                {{ $description ?? "This module hasn't been built yet. It will be developed in an upcoming phase." }}
            </p>
        </div>
    </div>
</x-layouts.erp>
