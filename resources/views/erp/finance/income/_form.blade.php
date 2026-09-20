@php $transaction ??= null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div>
        <label for="transaction_date" class="block text-sm font-medium text-gray-700">Date</label>
        <input type="date" name="transaction_date" id="transaction_date"
               value="{{ old('transaction_date', $transaction?->transaction_date?->format('Y-m-d') ?? date('Y-m-d')) }}" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('transaction_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="amount" class="block text-sm font-medium text-gray-700">Amount before tax (Rp)</label>
        <input type="number" step="0.01" min="0.01" name="amount" id="amount"
               value="{{ old('amount', $transaction->subtotal ?? '') }}" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        @php $taxes = \App\Models\Tax::where('is_active', true)->orderBy('type')->orderBy('name')->get(); @endphp
        <label for="tax_id" class="block text-sm font-medium text-gray-700">Tax</label>
        <select name="tax_id" id="tax_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <option value="" data-rate="0" data-type="">No tax</option>
            @foreach ($taxes as $tax)
                <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}" data-type="{{ $tax->type }}"
                        @selected(old('tax_id', $transaction->tax_id ?? '') == $tax->id)>
                    {{ $tax->name }} — {{ $tax->type === 'vat' ? '+' : '−' }}{{ rtrim(rtrim($tax->rate, '0'), '.') }}%
                </option>
            @endforeach
        </select>
        @error('tax_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        <p id="tax-preview" class="mt-2 text-sm text-gray-500"></p>
    </div>

    <div>
        <label for="account_id" class="block text-sm font-medium text-gray-700">Account</label>
        <select name="account_id" id="account_id" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <option value="">— Select Account —</option>
            @foreach (\App\Models\Account::where('is_active', true)->get() as $account)
                <option value="{{ $account->id }}" @selected(old('account_id', $transaction->account_id ?? '') == $account->id)>
                    {{ $account->name }}
                </option>
            @endforeach
        </select>
        @error('account_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
        <select name="category_id" id="category_id" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <option value="">— Select Category —</option>
            @foreach (\App\Models\Category::where('type', 'income')->where('is_active', true)->get() as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $transaction->category_id ?? '') == $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        @error('category_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="source" class="block text-sm font-medium text-gray-700">Source / Client</label>
        <input type="text" name="source" id="source"
               value="{{ old('source', $transaction->source ?? '') }}"
               placeholder="e.g., PT Maju Sejahtera, John Doe"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('source') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
        <textarea name="description" id="description" rows="2"
                  placeholder="What is this income for?"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('description', $transaction->description ?? '') }}</textarea>
        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="payment_method" class="block text-sm font-medium text-gray-700">Payment Method</label>
        <input type="text" name="payment_method" id="payment_method"
               value="{{ old('payment_method', $transaction->payment_method ?? '') }}"
               placeholder="e.g., Bank Transfer, Cash"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('payment_method') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
        <textarea name="notes" id="notes" rows="2"
                  placeholder="Internal notes"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('notes', $transaction->notes ?? '') }}</textarea>
        @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="inline-flex items-center rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark">
        {{ $transaction ? 'Update Income' : 'Record Income' }}
    </button>
    <a href="{{ route('finance.income') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
</div>

<script>
    (function () {
        const amount = document.getElementById('amount');
        const tax = document.getElementById('tax_id');
        const preview = document.getElementById('tax-preview');
        const fmt = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        function update() {
            const opt = tax.options[tax.selectedIndex];
            const base = parseFloat(amount.value) || 0;
            const rate = parseFloat(opt.dataset.rate) || 0;
            if (!opt.value || !base) { preview.textContent = ''; return; }
            const t = Math.round(base * rate) / 100;
            const total = opt.dataset.type === 'vat' ? base + t : base - t;
            preview.textContent = 'Tax: Rp ' + fmt.format(t) + ' — Total ' + (opt.dataset.type === 'vat' ? 'incl. tax' : 'after withholding') + ': Rp ' + fmt.format(total);
        }
        amount.addEventListener('input', update);
        tax.addEventListener('change', update);
        update();
    })();
</script>
