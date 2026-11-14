{{-- The brief a customer fills in to ask for custom work. Used by the public QR form and by the portal. Needs $packages. --}}
@php
    $input = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm';
    $catalog = $packages->mapWithKeys(fn ($package) => [$package->id => [
        'price' => (float) $package->starting_price,
        'days' => $package->estimated_days,
        'revisions' => $package->included_revisions,
        'description' => $package->description,
        'features' => $package->featureList(),
        'addons' => $package->addons ?? [],
        'timelines' => $package->timelines ?? [],
    ]]);
@endphp
<div x-data='{ catalog: @json($catalog), packageId: @json((string) old("service_package_id", "")), picked: @json(old("addons", [])), timeline: @json((string) old("timeline_days", "")),
              rupiah(n) { return "Rp " + new Intl.NumberFormat("id-ID").format(n); },
              get current() { return this.catalog[this.packageId] || null; } }' class="space-y-4">
    @if ($packages->isNotEmpty())
        <div>
            <label for="service_package_id" class="block text-sm font-medium text-gray-700">Package</label>
            <select name="service_package_id" id="service_package_id" x-model="packageId" @change="picked = []; timeline = ''" class="{{ $input }}">
                <option value="">Custom / not sure yet: quote it for me</option>
                @foreach ($packages as $package)
                    <option value="{{ $package->id }}">{{ $package->name }}: from Rp {{ number_format((float) $package->starting_price, 0, ',', '.') }}</option>
                @endforeach
            </select>
            @error('service_package_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

            <div x-show="current" x-cloak class="mt-2 rounded-md bg-brand-50 px-3 py-3 text-sm text-gray-700">
                <p x-show="current && current.description" x-text="current && current.description"></p>
                <ul class="mt-1 list-disc pl-5 text-xs text-gray-600">
                    <template x-for="feature in (current ? current.features : [])" :key="feature"><li x-text="feature"></li></template>
                </ul>
                <p class="mt-2 text-xs text-gray-500">
                    Starting price <span class="font-semibold text-gray-700" x-text="current && rupiah(current.price)"></span>
                    <template x-if="current && current.days"><span> · about <span x-text="current.days"></span> working days</span></template>
                    · <span x-text="current && current.revisions"></span> revision(s) included.
                    The final price is confirmed in your quote, based on your brief.
                </p>
                <div x-show="current && current.timelines.length" x-cloak class="mt-3">
                    <p class="text-xs font-semibold text-gray-700">How fast do you need it?</p>
                    <label class="mt-1 flex items-center gap-2 text-sm text-gray-700">
                        <input type="radio" name="timeline_days" value="" x-model="timeline" class="border-gray-300 text-brand focus:ring-brand">
                        <span>No preference: we propose the timing in the quote</span>
                    </label>
                    <template x-for="option in (current ? current.timelines : [])" :key="option.days">
                        <label class="mt-1 flex items-center gap-2 text-sm text-gray-700">
                            <input type="radio" name="timeline_days" :value="String(option.days)" x-model="timeline" class="border-gray-300 text-brand focus:ring-brand">
                            <span><span x-text="option.days"></span> working days
                                <span class="text-gray-500" x-text="option.price > 0 ? '(+ ' + rupiah(option.price) + ', rush)' : (option.price < 0 ? '(- ' + rupiah(-option.price) + ' for the longer time)' : '(standard price)')"></span>
                            </span>
                        </label>
                    </template>
                    <p class="mt-1 text-xs text-gray-500">The faster we deliver, the higher the price may be. The final price and date are confirmed in your quote.</p>
                </div>
                <div x-show="current && current.addons.length" class="mt-3">
                    <p class="text-xs font-semibold text-gray-700">Optional add-ons</p>
                    <template x-for="addon in (current ? current.addons : [])" :key="addon.label">
                        <label class="mt-1 flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="addons[]" :value="addon.label" x-model="picked" class="rounded border-gray-300 text-brand focus:ring-brand">
                            <span><span x-text="addon.label"></span> <span class="text-gray-500">(+ <span x-text="rupiah(addon.price)"></span>)</span></span>
                        </label>
                    </template>
                </div>
            </div>
        </div>
    @endif

    <div>
        <label for="title" class="block text-sm font-medium text-gray-700">What do you need? <span class="text-red-600">*</span></label>
        <input type="text" name="title" id="title" value="{{ old('title') }}" required maxlength="255" placeholder="e.g. Monthly sales dashboard per branch" class="{{ $input }}">
        @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="purpose" class="block text-sm font-medium text-gray-700">Purpose and who will use it <span class="text-red-600">*</span></label>
        <textarea name="purpose" id="purpose" rows="3" required class="{{ $input }}" placeholder="What decision should this dashboard help with, and who looks at it?">{{ old('purpose') }}</textarea>
        @error('purpose') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="kpis" class="block text-sm font-medium text-gray-700">KPIs or questions it must answer</label>
        <textarea name="kpis" id="kpis" rows="3" class="{{ $input }}" placeholder="e.g. revenue per month, top 10 products, margin per branch">{{ old('kpis') }}</textarea>
        @error('kpis') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="data_sources" class="block text-sm font-medium text-gray-700">Where does the data come from? <span class="text-red-600">*</span></label>
        <textarea name="data_sources" id="data_sources" rows="3" required class="{{ $input }}" placeholder="e.g. Excel export from our POS, 2 CSV files, Google Sheet">{{ old('data_sources') }}</textarea>
        @error('data_sources') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="data_volume" class="block text-sm font-medium text-gray-700">Data volume</label>
            <input type="text" name="data_volume" id="data_volume" value="{{ old('data_volume') }}" maxlength="255" placeholder="e.g. about 20,000 rows" class="{{ $input }}">
            @error('data_volume') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="excel_version" class="block text-sm font-medium text-gray-700">Excel version</label>
            <input type="text" name="excel_version" id="excel_version" value="{{ old('excel_version') }}" maxlength="100" placeholder="e.g. Microsoft 365 on Windows" class="{{ $input }}">
            @error('excel_version') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="deadline" class="block text-sm font-medium text-gray-700">Deadline (if any)</label>
            <input type="date" name="deadline" id="deadline" value="{{ old('deadline') }}" min="{{ today()->toDateString() }}" class="{{ $input }}">
            @error('deadline') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="flex items-end">
            <label class="flex items-center gap-2 pb-2 text-sm text-gray-700">
                <input type="hidden" name="needs_refresh" value="0">
                <input type="checkbox" name="needs_refresh" value="1" @checked(old('needs_refresh')) class="rounded border-gray-300 text-brand focus:ring-brand">
                The data will be updated regularly (refresh needed)
            </label>
        </div>
    </div>
    <div>
        <label for="notes" class="block text-sm font-medium text-gray-700">Anything else</label>
        <textarea name="notes" id="notes" rows="2" class="{{ $input }}" placeholder="References, branding colours, special requests">{{ old('notes') }}</textarea>
        @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="files" class="block text-sm font-medium text-gray-700">Sample data or references</label>
        <input type="file" name="files[]" id="files" multiple class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-brand hover:file:bg-brand-100">
        <p class="mt-1 text-xs text-gray-500">Up to {{ \App\Services\ServiceOrders::MAX_FILES }} files, {{ (int) (\App\Models\ServiceOrder::FILE_MAX_KB / 1024) }} MB each (Excel, CSV, PDF, ZIP, images). Please remove confidential details first; anonymised sample data is enough.</p>
        @error('files') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        @foreach ($errors->get('files.*') as $messages)
            @foreach ($messages as $message) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @endforeach
        @endforeach
    </div>
</div>
