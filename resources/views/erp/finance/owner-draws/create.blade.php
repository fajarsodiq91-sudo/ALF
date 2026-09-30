<x-layouts.erp title="Owner Draw">
    @php
        $taxes = \App\Models\Tax::where('is_active', true)->where('type', 'withholding')->orderBy('name')->get();
        $dividendTax = $taxes->firstWhere('name', 'PPh Dividen (10%)');
        $salaryTax = $taxes->firstWhere('name', 'PPh 21 (5%)');
        $type = old('draw_type', 'dividend');
    @endphp
    <div class="max-w-2xl">
        <x-erp.flash />
        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
            <p class="text-sm text-gray-600 mb-5">
                Record money the owner takes from the company. Withholding tax is deducted from the amount; only the net leaves the account now, and the tax stays owed until paid under Tax Payments.
                Dividends are a distribution of profit (not an operating cost); salary is an expense.
            </p>
            <form action="{{ route('finance.owner-draws.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="draw_type" class="block text-sm font-medium text-gray-700">Type</label>
                        <select name="draw_type" id="draw_type" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                            <option value="dividend" @selected($type === 'dividend')>Dividend (PPh final 10%)</option>
                            <option value="salary" @selected($type === 'salary')>Salary (PPh 21)</option>
                        </select>
                        @error('draw_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="transaction_date" class="block text-sm font-medium text-gray-700">Date</label>
                        <input type="date" name="transaction_date" id="transaction_date" value="{{ old('transaction_date', date('Y-m-d')) }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                        @error('transaction_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="payee" class="block text-sm font-medium text-gray-700">Owner / CEO name</label>
                        <input type="text" name="payee" id="payee" value="{{ old('payee') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                        @error('payee') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700">Gross amount before tax (Rp)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="amount" value="{{ old('amount') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                        @error('amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="tax_id" class="block text-sm font-medium text-gray-700">Tax</label>
                        <select name="tax_id" id="tax_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                            @foreach ($taxes as $tax)
                                <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}"
                                        @selected(old('tax_id', $type === 'salary' ? $salaryTax?->id : $dividendTax?->id) == $tax->id)>
                                    {{ $tax->name }} — {{ rtrim(rtrim($tax->rate, '0'), '.') }}%
                                </option>
                            @endforeach
                        </select>
                        @error('tax_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="tax_amount" class="block text-sm font-medium text-gray-700">Tax amount override (Rp, optional)</label>
                        <input type="number" step="0.01" min="0" name="tax_amount" id="tax_amount" value="{{ old('tax_amount') }}"
                               placeholder="Leave blank to use the rate"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">For salary, enter the exact PPh 21 from your progressive calculation.</p>
                        @error('tax_amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="account_id" class="block text-sm font-medium text-gray-700">Paid from account</label>
                        <select name="account_id" id="account_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                            <option value="">— Select Account —</option>
                            @foreach (\App\Models\Account::where('is_active', true)->get() as $account)
                                <option value="{{ $account->id }}" @selected(old('account_id') == $account->id)>{{ $account->name }}</option>
                            @endforeach
                        </select>
                        @error('account_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea name="notes" id="notes" rows="2"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('notes') }}</textarea>
                        @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <x-finance.proof-fields :record="null" class="sm:col-span-2 border-t border-gray-100 pt-5" />
                </div>

                <p id="draw-preview" class="mt-4 text-sm text-gray-500"></p>

                <div class="mt-6 flex items-center gap-3">
                    <button type="submit" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white shadow-sm">Record Owner Draw</button>
                    <a href="{{ route('finance.expenses') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const type = document.getElementById('draw_type');
            const tax = document.getElementById('tax_id');
            const amount = document.getElementById('amount');
            const override = document.getElementById('tax_amount');
            const preview = document.getElementById('draw-preview');
            const defaults = { dividend: @json($dividendTax?->id), salary: @json($salaryTax?->id) };
            const fmt = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            function update() {
                const base = parseFloat(amount.value) || 0;
                const rate = parseFloat(tax.options[tax.selectedIndex]?.dataset.rate) || 0;
                const t = override.value !== '' ? parseFloat(override.value) || 0 : Math.round(base * rate) / 100;
                preview.textContent = base ? 'Tax withheld: Rp ' + fmt.format(t) + ' — Owner receives: Rp ' + fmt.format(base - t) : '';
            }
            type.addEventListener('change', () => { if (defaults[type.value]) tax.value = defaults[type.value]; update(); });
            [amount, tax, override].forEach(el => el.addEventListener('input', update));
            tax.addEventListener('change', update);
            update();
        })();
    </script>
</x-layouts.erp>
