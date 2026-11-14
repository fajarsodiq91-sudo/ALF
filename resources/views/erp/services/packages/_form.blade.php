@php $input = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm'; @endphp
<div class="space-y-4">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">Name <span class="text-red-600">*</span></label>
        <input type="text" name="name" id="name" value="{{ old('name', $package->name) }}" required maxlength="255" placeholder="e.g. Excel Dashboard Standard" class="{{ $input }}">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="description" class="block text-sm font-medium text-gray-700">Short description</label>
        <textarea name="description" id="description" rows="2" maxlength="1000" class="{{ $input }}">{{ old('description', $package->description) }}</textarea>
        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="features" class="block text-sm font-medium text-gray-700">What is included (one per line)</label>
        <textarea name="features" id="features" rows="4" class="{{ $input }}" placeholder="1 data source&#10;2-3 dashboard pages&#10;Slicers and Power Query">{{ old('features', $package->features) }}</textarea>
        @error('features') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label for="starting_price" class="block text-sm font-medium text-gray-700">Starting price (Rp) <span class="text-red-600">*</span></label>
            <input type="number" min="0" step="1" name="starting_price" id="starting_price" value="{{ old('starting_price', $package->starting_price ? (int) $package->starting_price : '') }}" required class="{{ $input }}">
            @error('starting_price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="included_revisions" class="block text-sm font-medium text-gray-700">Revisions included <span class="text-red-600">*</span></label>
            <input type="number" min="0" max="20" name="included_revisions" id="included_revisions" value="{{ old('included_revisions', $package->included_revisions) }}" required class="{{ $input }}">
            @error('included_revisions') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="estimated_days" class="block text-sm font-medium text-gray-700">Estimated working days</label>
            <input type="number" min="1" max="365" name="estimated_days" id="estimated_days" value="{{ old('estimated_days', $package->estimated_days) }}" class="{{ $input }}">
            @error('estimated_days') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
    <div>
        <label for="addons_text" class="block text-sm font-medium text-gray-700">Add-ons (one per line: name | price)</label>
        <textarea name="addons_text" id="addons_text" rows="4" class="{{ $input }}" placeholder="Extra dashboard page | 300000&#10;Automatic refresh (Power Query) | 500000&#10;Handover session | 400000">{{ old('addons_text', $package->addonsText()) }}</textarea>
        <p class="mt-1 text-xs text-gray-500">Customers can tick these in their brief; each one becomes a line in the quote. Lines without a valid price are ignored.</p>
        @error('addons_text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="timelines_text" class="block text-sm font-medium text-gray-700">Turnaround options (one per line: working days | price change)</label>
        <textarea name="timelines_text" id="timelines_text" rows="4" class="{{ $input }}" placeholder="3 | 750000&#10;7 | 0&#10;14 | -150000">{{ old('timelines_text', $package->timelinesText()) }}</textarea>
        <p class="mt-1 text-xs text-gray-500">The customer picks one and its price change is added to the quote. Use a positive number for a rush (faster) job, 0 for the standard time, and a negative number when a longer wait is cheaper. Leave empty to offer no choice (the estimated days above apply).</p>
        @error('timelines_text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="sort_order" class="block text-sm font-medium text-gray-700">Sort order</label>
            <input type="number" min="0" max="1000" name="sort_order" id="sort_order" value="{{ old('sort_order', $package->sort_order ?? 0) }}" class="{{ $input }}">
        </div>
        <div class="flex items-end">
            <label class="flex items-center gap-2 pb-2 text-sm text-gray-700">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $package->is_active)) class="rounded border-gray-300 text-brand focus:ring-brand">
                Active (customers can choose it)
            </label>
        </div>
    </div>
    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md transition-all duration-150">Save</button>
        <a href="{{ route('services.packages.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
    </div>
</div>
