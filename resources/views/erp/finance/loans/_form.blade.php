@php $loan ??= null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div class="sm:col-span-2">
        <label for="direction" class="block text-sm font-medium text-gray-700">Direction</label>
        <select name="direction" id="direction" required @disabled($loan)
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            @foreach (\App\Models\Loan::DIRECTIONS as $value => $label)
                <option value="{{ $value }}" @selected(old('direction', $loan->direction ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @if ($loan) <input type="hidden" name="direction" value="{{ $loan->direction }}"> @endif
        <p class="mt-1 text-xs text-gray-500">Owner borrows: cash leaves the company account now and comes back as repayments. Company borrows: cash enters the company account now and leaves as repayments.</p>
        @error('direction') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="loan_date" class="block text-sm font-medium text-gray-700">Date</label>
        <input type="date" name="loan_date" id="loan_date"
               value="{{ old('loan_date', $loan?->loan_date?->format('Y-m-d') ?? date('Y-m-d')) }}" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('loan_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="amount" class="block text-sm font-medium text-gray-700">Amount (Rp)</label>
        <input type="number" step="0.01" min="0.01" name="amount" id="amount"
               value="{{ old('amount', $loan->amount ?? '') }}" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="account_id" class="block text-sm font-medium text-gray-700">Company Account</label>
        <select name="account_id" id="account_id" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <option value="">— Select Account —</option>
            @foreach (\App\Models\Account::where('is_active', true)->get() as $account)
                <option value="{{ $account->id }}" @selected(old('account_id', $loan->account_id ?? '') == $account->id)>{{ $account->name }}</option>
            @endforeach
        </select>
        @error('account_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="party_name" class="block text-sm font-medium text-gray-700">Owner / Party Name</label>
        <input type="text" name="party_name" id="party_name" value="{{ old('party_name', $loan->party_name ?? '') }}" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('party_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
        <textarea name="description" id="description" rows="2"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('description', $loan->description ?? '') }}</textarea>
        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
        <textarea name="notes" id="notes" rows="2"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('notes', $loan->notes ?? '') }}</textarea>
        @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="inline-flex items-center rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark">
        {{ $loan ? 'Update Loan' : 'Record Loan' }}
    </button>
    <a href="{{ $loan ? route('finance.loans.show', $loan) : route('finance.loans') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
</div>
