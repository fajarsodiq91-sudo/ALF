<x-layouts.erp :title="'Tutorial — '.$topic['title']">
    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            @include('erp.tutorial._nav', ['active' => $slug])

            <div class="lg:col-span-3 space-y-4">
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6 flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $topic['group'] }}</p>
                        <h2 class="text-lg font-semibold text-gray-800">{{ $topic['title'] }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $topic['summary'] }}</p>
                    </div>
                    @if ($url = \App\Services\Tutorial::url($slug))
                        <a href="{{ $url }}" class="shrink-0 inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white shadow-sm hover:shadow-md transition">Buka halaman</a>
                    @endif
                </div>

                @include('erp.tutorial.topics.'.$slug)

                <a href="{{ route('tutorial.index') }}" class="inline-block text-sm text-gray-500 hover:text-gray-700">&larr; Kembali ke ringkasan tutorial</a>
            </div>
        </div>
    </div>
</x-layouts.erp>
