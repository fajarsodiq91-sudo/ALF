@php $asset ??= null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div class="">
        <label for="asset_code" class="block text-sm font-medium text-gray-700">Asset Code</label>
        <input type="text" name="asset_code" id="asset_code" value="{{ old('asset_code', $asset->asset_code ?? '') }}" required 
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('asset_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
        <input type="text" name="name" id="name" value="{{ old('name', $asset->name ?? '') }}" required 
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
        <select name="category" id="category" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            @foreach (\App\Models\Asset::CATEGORIES as $value => $label)
                <option value="{{ $value }}" @selected(old('category', $asset->category ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
        <select name="status" id="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            @foreach (\App\Models\Asset::STATUSES as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $asset->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="purchase_date" class="block text-sm font-medium text-gray-700">Purchase Date</label>
        <input type="date" name="purchase_date" id="purchase_date" value="{{ old('purchase_date', optional($asset?->purchase_date)->format('Y-m-d')) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('purchase_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="purchase_cost" class="block text-sm font-medium text-gray-700">Purchase Cost (Rp)</label>
        <input type="number" name="purchase_cost" id="purchase_cost" value="{{ old('purchase_cost', $asset->purchase_cost ?? 0) }}" required step="0.01" min="0"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('purchase_cost') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
        <input type="text" name="location" id="location" value="{{ old('location', $asset->location ?? '') }}" 
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('location') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="assigned_to" class="block text-sm font-medium text-gray-700">Assigned To</label>
        <input type="text" name="assigned_to" id="assigned_to" value="{{ old('assigned_to', $asset->assigned_to ?? '') }}" 
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('assigned_to') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
        <textarea name="notes" id="notes" rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('notes', $asset->notes ?? '') }}</textarea>
        @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark">
        {{ $asset ? 'Update Asset' : 'Create Asset' }}
    </button>
    <a href="{{ route('assets.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
</div>
