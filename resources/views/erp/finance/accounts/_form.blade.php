@php $account ??= null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div class="sm:col-span-2">
        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
        <input type="text" name="name" id="name" value="{{ old('name', $account->name ?? '') }}" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="account_type" class="block text-sm font-medium text-gray-700">Account Type</label>
        <select name="account_type" id="account_type" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            @foreach (['cash' => 'Cash', 'bank' => 'Bank', 'e-wallet' => 'E-Wallet', 'other' => 'Other'] as $value => $label)
                <option value="{{ $value }}" @selected(old('account_type', $account->account_type ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('account_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="account_number" class="block text-sm font-medium text-gray-700">Account Number</label>
        <input type="text" name="account_number" id="account_number" value="{{ old('account_number', $account->account_number ?? '') }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('account_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="opening_balance" class="block text-sm font-medium text-gray-700">Opening Balance (Rp)</label>
        <input type="number" step="0.01" min="0" name="opening_balance" id="opening_balance"
               value="{{ old('opening_balance', $account->opening_balance ?? 0) }}" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('opening_balance') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center gap-2 mt-6">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" id="is_active" value="1"
               @checked(old('is_active', $account->is_active ?? true))
               class="rounded border-gray-300 text-brand focus:ring-brand">
        <label for="is_active" class="text-sm text-gray-700">Active</label>
    </div>

    <div class="sm:col-span-2">
        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
        <textarea name="description" id="description" rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('description', $account->description ?? '') }}</textarea>
        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="inline-flex items-center rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark">
        {{ $account ? 'Update Account' : 'Create Account' }}
    </button>
    <a href="{{ route('finance.accounts') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
</div>
