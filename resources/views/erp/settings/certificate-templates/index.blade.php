<x-layouts.erp title="Certificate Templates">
    <div class="max-w-4xl">
        <x-erp.flash />

        <div class="mb-4 flex items-center justify-between">
            <p class="text-sm text-gray-500">A4 landscape PNG backgrounds. Name, number, date, sentence, QR code and signature are placed over them automatically.</p>
            <a href="{{ route('settings.certificate-templates.create') }}" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white shadow-sm hover:shadow-md transition">Upload template</a>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @forelse ($templates as $template)
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-md">
                    <img src="{{ asset('storage/'.$template->background_path) }}" alt="{{ $template->name }}" class="w-full rounded border border-gray-200">
                    <div class="mt-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">{{ $template->name }} @if ($template->is_default)<span class="ml-1 rounded bg-green-100 px-2 py-0.5 text-xs text-green-700">Default</span>@endif</p>
                            <p class="text-xs text-gray-500">Used by {{ $template->programs_count }} {{ $template->programs_count === 1 ? 'program' : 'programs' }}</p>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <a href="{{ route('settings.certificate-templates.preview', $template) }}" target="_blank" class="text-brand hover:text-brand-dark">Preview</a>
                            <a href="{{ route('settings.certificate-templates.edit', $template) }}" class="text-brand hover:text-brand-dark">Edit</a>
                            <form action="{{ route('settings.certificate-templates.destroy', $template) }}" method="POST" onsubmit="return confirm('Delete this template? Programs using it fall back to the default.')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:text-red-800">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">No templates yet. Until one is uploaded, certificates use the built-in design.</p>
            @endforelse
        </div>
    </div>
</x-layouts.erp>
