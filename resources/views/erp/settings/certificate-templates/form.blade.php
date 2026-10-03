<x-layouts.erp :title="$template->exists ? 'Edit Certificate Template' : 'Upload Certificate Template'">
    <div class="max-w-3xl">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-md">
            <form action="{{ $template->exists ? route('settings.certificate-templates.update', $template) : route('settings.certificate-templates.store') }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 gap-5">
                @csrf
                @if ($template->exists) @method('PUT') @endif

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $template->name) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="background" class="block text-sm font-medium text-gray-700">Template (PNG, A4 landscape, 300 DPI = 3508 × 2480 px)</label>
                    <input type="file" name="background" id="background" accept="image/png" @required(! $template->exists) class="mt-1 block w-full text-sm text-gray-600">
                    @if ($template->exists)
                        <p class="mt-1 text-xs text-gray-500">Leave empty to keep the current image.</p>
                    @endif
                    @error('background') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $template->is_default)) class="rounded border-gray-300 text-brand focus:ring-brand">
                    Default template (used by programs without a template of their own)
                </label>

                <div class="border-t border-gray-100 pt-5">
                    <h2 class="text-sm font-semibold text-gray-800">Field positions (mm)</h2>
                    <p class="mb-3 text-xs text-gray-500">X is the horizontal centre of the field, Y its top edge, both measured from the page's top-left corner (page is 297 × 210). Width is the text box width, or the image size for QR and signature. Save, then use Preview to check.</p>
                    <div class="grid grid-cols-4 items-center gap-x-3 gap-y-2 text-sm">
                        <span class="font-medium text-gray-500">Field</span><span class="font-medium text-gray-500">X</span><span class="font-medium text-gray-500">Y</span><span class="font-medium text-gray-500">Width</span>
                        @foreach (\App\Models\CertificateTemplate::FIELDS as $key => [$label])
                            <span class="text-gray-700">{{ $label }}</span>
                            @foreach (['x', 'y', 'w'] as $axis)
                                <input type="number" step="0.1" min="0" max="297" name="layout[{{ $key }}][{{ $axis }}]" value="{{ old("layout.$key.$axis", $template->positions()[$key][$axis]) }}" class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                            @endforeach
                        @endforeach
                    </div>
                    @error('layout.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white shadow-sm hover:shadow-md transition">Save</button>
                    <a href="{{ route('settings.certificate-templates.index') }}" class="text-sm text-gray-600 hover:text-gray-800">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.erp>
