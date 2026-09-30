<x-layouts.erp title="Master Data — Website">
    <div class="max-w-6xl">
        <x-erp.flash />

        <p class="mb-4 text-sm text-gray-500">
            Kelola isi situs publik perusahaan: judul, paragraf, tombol, gambar, kontak, footer, dan SEO. Perubahan langsung tampil di situs setelah disimpan.
        </p>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            @include('erp.master-data._nav', ['active' => 'site-page:'.$page])

            <div class="lg:col-span-3 space-y-4">
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4">
                    <h2 class="text-base font-semibold text-gray-800">Website — {{ $definition['label'] }}</h2>
                    <p class="text-sm text-gray-500">{{ $definition['description'] }} Text is plain: line breaks are kept, HTML is not. Leave a field empty to bring back its default.</p>
                    @if ($page !== 'global' && $page !== 'thank-you')
                        <p class="mt-2 text-sm">
                            <a href="{{ route($page === 'home' ? 'home' : $page) }}" target="_blank" rel="noopener" class="text-brand hover:text-brand-dark font-medium">View this page &rarr;</a>
                        </p>
                    @endif
                </div>

                @if (! empty($definition['lists']))
                    <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4 text-sm text-gray-600">
                        Cards and repeating items on this page are managed as lists:
                        @foreach ($definition['lists'] as $type)
                            <a href="{{ route('masterdata.site.items.index', $type) }}" class="ml-2 text-brand hover:text-brand-dark font-medium">{{ \App\Models\SiteItem::TYPES[$type]['label'] }}</a>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('masterdata.site.page.update', $page) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('PUT')

                    @foreach ($definition['sections'] as $title => $fields)
                        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4 space-y-4">
                            <h3 class="text-sm font-semibold text-gray-800">{{ $title }}</h3>

                            @foreach ($fields as $name => $field)
                                @php $path = $page.'.'.$name; @endphp
                                <div>
                                    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700">{{ $field['label'] }}</label>

                                    @if ($field['type'] === 'image')
                                        @php $uploaded = \App\Services\SiteContent::uploadedImage($path); @endphp
                                        <div class="mt-1 flex flex-wrap items-center gap-4">
                                            <img src="{{ \App\Services\SiteContent::image($path) }}" alt="" class="h-20 max-w-[12rem] rounded-md border border-gray-200 bg-gray-50 object-contain p-1">
                                            <div class="space-y-2">
                                                <input type="file" name="images[{{ $name }}]" id="{{ $name }}" accept="image/png,image/jpeg,image/webp"
                                                       class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand hover:file:bg-red-100">
                                                @if ($uploaded)
                                                    <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                                                        <input type="checkbox" name="remove[{{ $name }}]" value="1" class="rounded border-gray-300 text-brand focus:ring-brand">
                                                        Go back to the default image
                                                    </label>
                                                @endif
                                            </div>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">JPG, PNG or WebP, up to 4 MB.</p>
                                        @error('images.'.$name) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    @else
                                        @if ($field['type'] === 'textarea')
                                            <textarea name="content[{{ $name }}]" id="{{ $name }}" rows="4" maxlength="{{ $field['max'] ?? 2000 }}"
                                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('content.'.$name, \App\Services\SiteContent::text($path)) }}</textarea>
                                        @else
                                            <input type="{{ ['email' => 'email', 'url' => 'url'][$field['type']] ?? 'text' }}" name="content[{{ $name }}]" id="{{ $name }}"
                                                   value="{{ old('content.'.$name, \App\Services\SiteContent::text($path)) }}" maxlength="{{ $field['max'] ?? 255 }}"
                                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                        @endif
                                        @error('content.'.$name) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    @endif

                                    @isset($field['hint'])
                                        <p class="mt-1 text-xs text-gray-500">{{ $field['hint'] }}</p>
                                    @endisset
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                    <button type="submit" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">Save</button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.erp>
