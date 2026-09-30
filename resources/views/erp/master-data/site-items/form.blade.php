@php
    $editing = $item->exists;
    $action = $editing ? route('masterdata.site.items.update', [$type, $item]) : route('masterdata.site.items.store', $type);
@endphp
<x-layouts.erp title="Master Data — Website">
    <div class="max-w-6xl">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            @include('erp.master-data._nav', ['active' => 'site-list:'.$type])

            <div class="lg:col-span-3 space-y-4">
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4">
                    <h2 class="text-base font-semibold text-gray-800">{{ $editing ? 'Edit' : 'Add' }} {{ $definition['singular'] }}</h2>
                    <p class="text-sm text-gray-500">{{ $definition['label'] }} — {{ $definition['description'] }} Text is plain: line breaks are kept, HTML is not.</p>
                </div>

                <form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow-md border border-gray-200 p-6 space-y-5">
                    @csrf
                    @if ($editing) @method('PUT') @endif

                    @foreach ($definition['fields'] as $name => $field)
                        <div>
                            <label for="{{ $name }}" class="block text-sm font-medium text-gray-700">{{ $field['label'] }} @if ($field['required'] ?? false)<span class="text-red-500">*</span>@endif</label>

                            @if ($name === 'image')
                                <div class="mt-1 flex flex-wrap items-center gap-4">
                                    @if ($item->imageUrl())
                                        <img src="{{ $item->imageUrl() }}" alt="" class="h-20 w-20 rounded-md border border-gray-200 bg-gray-50 object-cover">
                                    @endif
                                    <div class="space-y-2">
                                        <input type="file" name="image" id="image" accept="image/png,image/jpeg,image/webp"
                                               class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand hover:file:bg-red-100">
                                        @if ($item->imageUrl())
                                            <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                                                <input type="checkbox" name="remove_image" value="1" class="rounded border-gray-300 text-brand focus:ring-brand">
                                                Remove the current image
                                            </label>
                                        @endif
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">JPG, PNG or WebP, up to 4 MB.</p>
                            @elseif ($name === 'body')
                                <textarea name="body" id="body" rows="4" maxlength="2000" @required($field['required'] ?? false)
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('body', $item->body) }}</textarea>
                            @elseif ($name === 'category')
                                <select name="category" id="category" @required($field['required'] ?? false)
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                    <option value="">Choose…</option>
                                    @foreach (\App\Services\MasterData::options('portfolio_category', $item->category) as $code => $label)
                                        <option value="{{ $code }}" @selected(old('category', $item->category) === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            @elseif ($name === 'number')
                                <input type="number" name="number" id="number" min="0" max="1000000" value="{{ old('number', $item->number) }}" @required($field['required'] ?? false)
                                       class="mt-1 block w-40 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                            @else
                                <input type="text" name="{{ $name }}" id="{{ $name }}" value="{{ old($name, $item->{$name}) }}" @required($field['required'] ?? false)
                                       maxlength="{{ $name === 'icon' ? 16 : ($name === 'link_url' ? 500 : ($field['max'] ?? 255)) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                            @endif

                            @isset($field['hint'])
                                <p class="mt-1 text-xs text-gray-500">{{ $field['hint'] }}</p>
                            @endisset
                            @error($name) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endforeach

                    <div class="flex flex-wrap items-end gap-6 border-t border-gray-100 pt-5">
                        <div>
                            <label for="sort_order" class="block text-sm font-medium text-gray-700">Order</label>
                            <input type="number" name="sort_order" id="sort_order" min="0" value="{{ old('sort_order', $editing ? $item->sort_order : '') }}" placeholder="Last"
                                   class="mt-1 block w-24 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                            @error('sort_order') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <label class="inline-flex items-center gap-2 pb-2 text-sm text-gray-700">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active)) class="rounded border-gray-300 text-brand focus:ring-brand">
                            Show on the website
                        </label>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">Save</button>
                        <a href="{{ route('masterdata.site.items.index', $type) }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.erp>
