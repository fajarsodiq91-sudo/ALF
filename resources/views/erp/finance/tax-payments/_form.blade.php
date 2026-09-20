@php $taxPayment ??= null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div>
        <label for="tax_type" class="block text-sm font-medium text-gray-700">Tax</label>
        <select name="tax_type" id="tax_type" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <option value="vat" @selected(old('tax_type', $taxPayment->tax_type ?? '') === 'vat')>PPN / VAT</option>
            <option value="withholding" @selected(old('tax_type', $taxPayment->tax_type ?? '') === 'withholding')>PPh / Withholding</option>
        </select>
        @error('tax_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="period" class="block text-sm font-medium text-gray-700">Tax period (month)</label>
        <input type="month" name="period" id="period"
               value="{{ old('period', $taxPayment->period ?? now()->subMonth()->format('Y-m')) }}" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('period') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="payment_date" class="block text-sm font-medium text-gray-700">Payment date</label>
        <input type="date" name="payment_date" id="payment_date"
               value="{{ old('payment_date', $taxPayment?->payment_date?->format('Y-m-d') ?? date('Y-m-d')) }}" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('payment_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="amount" class="block text-sm font-medium text-gray-700">Amount (Rp)</label>
        <input type="number" step="0.01" min="0.01" name="amount" id="amount"
               value="{{ old('amount', $taxPayment->amount ?? '') }}" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="account_id" class="block text-sm font-medium text-gray-700">Paid from account</label>
        <select name="account_id" id="account_id" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <option value="">— Select Account —</option>
            @foreach (\App\Models\Account::where('is_active', true)->get() as $account)
                <option value="{{ $account->id }}" @selected(old('account_id', $taxPayment->account_id ?? '') == $account->id)>{{ $account->name }}</option>
            @endforeach
        </select>
        @error('account_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="reference" class="block text-sm font-medium text-gray-700">Reference (NTPN / billing code)</label>
        <input type="text" name="reference" id="reference" value="{{ old('reference', $taxPayment->reference ?? '') }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('reference') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
        <textarea name="notes" id="notes" rows="2"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('notes', $taxPayment->notes ?? '') }}</textarea>
        @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="inline-flex items-center rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark">
        {{ $taxPayment ? 'Update Payment' : 'Record Payment' }}
    </button>
    <a href="{{ route('finance.tax-payments') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
</div>
