@php $transfer ??= null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div>
        <label for="transfer_date" class="block text-sm font-medium text-gray-700">Date</label>
        <input type="date" name="transfer_date" id="transfer_date"
               value="{{ old('transfer_date', $transfer?->transfer_date?->format('Y-m-d') ?? date('Y-m-d')) }}" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('transfer_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="amount" class="block text-sm font-medium text-gray-700">Amount (Rp)</label>
        <input type="number" step="0.01" min="0.01" name="amount" id="amount"
               value="{{ old('amount', $transfer->amount ?? '') }}" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="from_account_id" class="block text-sm font-medium text-gray-700">From Account</label>
        <select name="from_account_id" id="from_account_id" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <option value="">— Select Source Account —</option>
            @foreach (\App\Models\Account::where('is_active', true)->get() as $account)
                <option value="{{ $account->id }}" @selected(old('from_account_id', $transfer->from_account_id ?? '') == $account->id)>
                    {{ $account->name }}
                </option>
            @endforeach
        </select>
        @error('from_account_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="to_account_id" class="block text-sm font-medium text-gray-700">To Account</label>
        <select name="to_account_id" id="to_account_id" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <option value="">— Select Destination Account —</option>
            @foreach (\App\Models\Account::where('is_active', true)->get() as $account)
                <option value="{{ $account->id }}" @selected(old('to_account_id', $transfer->to_account_id ?? '') == $account->id)>
                    {{ $account->name }}
                </option>
            @endforeach
        </select>
        @error('to_account_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="fee" class="block text-sm font-medium text-gray-700">Bank admin fee (Rp)</label>
        <input type="number" step="0.01" min="0" name="fee" id="fee"
               value="{{ old('fee', $transfer?->feeExpense?->amount ?? '') }}"
               placeholder="0 if the bank charges nothing"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        <p class="mt-1 text-xs text-gray-500">Deducted from the source account and recorded as an expense in the "Bank Charges" category.</p>
        @error('fee') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
        <textarea name="description" id="description" rows="3"
                  placeholder="Why is this transfer being made? (optional)"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('description', $transfer->description ?? '') }}</textarea>
        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="inline-flex items-center rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark">
        {{ $transfer ? 'Update Transfer' : 'Record Transfer' }}
    </button>
    <a href="{{ route('finance.transfers') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
</div>
