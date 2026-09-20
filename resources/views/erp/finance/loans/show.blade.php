<x-layouts.erp title="Loan {{ $loan->loan_number }}">
    <div class="max-w-4xl">
        <x-erp.flash />

        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('finance.loans') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">← Back to Loans</a>
            @can('finance.manage')
                <div class="flex items-center gap-3">
                    <a href="{{ route('finance.loans.edit', $loan) }}" class="text-brand hover:text-brand-dark text-sm font-medium">Edit</a>
                    <form action="{{ route('finance.loans.destroy', $loan) }}" method="POST" onsubmit="return confirm('Delete this loan?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-gray-400 hover:text-red-600 text-sm font-medium">Delete</button>
                    </form>
                </div>
            @endcan
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-500">Direction</dt><dd class="font-medium text-gray-900">{{ \App\Models\Loan::DIRECTIONS[$loan->direction] }}</dd></div>
                <div><dt class="text-gray-500">Party</dt><dd class="font-medium text-gray-900">{{ $loan->party_name }}</dd></div>
                <div><dt class="text-gray-500">Date</dt><dd class="font-medium text-gray-900">{{ $loan->loan_date->format('Y-m-d') }}</dd></div>
                <div><dt class="text-gray-500">Account</dt><dd class="font-medium text-gray-900">{{ $loan->account->name }}</dd></div>
                <div><dt class="text-gray-500">Amount</dt><dd class="font-medium text-gray-900">Rp {{ number_format($loan->amount, 2, ',', '.') }}</dd></div>
                <div><dt class="text-gray-500">Repaid</dt><dd class="font-medium text-gray-900">Rp {{ number_format($loan->repaidAmount(), 2, ',', '.') }}</dd></div>
                <div class="col-span-2"><dt class="text-gray-500">Outstanding</dt>
                    <dd class="text-2xl font-bold {{ $loan->isSettled() ? 'text-green-600' : 'text-orange-600' }}">Rp {{ number_format($loan->outstandingAmount(), 2, ',', '.') }}{{ $loan->isSettled() ? ' — settled' : '' }}</dd></div>
                @if ($loan->description)<div class="col-span-2"><dt class="text-gray-500">Description</dt><dd class="text-gray-900">{{ $loan->description }}</dd></div>@endif
                @if ($loan->notes)<div class="col-span-2"><dt class="text-gray-500">Notes</dt><dd class="text-gray-900">{{ $loan->notes }}</dd></div>@endif
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto mb-6">
            <div class="px-4 py-3 border-b border-gray-100"><h3 class="text-sm font-semibold text-gray-800">Repayments</h3></div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Repayment #</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Date</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Account</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Amount</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Notes</th>
                        @can('finance.manage')<th class="px-4 py-3"></th>@endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($loan->repayments as $repayment)
                        <tr>
                            <td class="px-4 py-3 font-mono text-gray-600">{{ $repayment->repayment_number }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $repayment->repayment_date->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ $repayment->account->name }}</td>
                            <td class="px-4 py-3 text-right font-medium">Rp {{ number_format($repayment->amount, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $repayment->notes ?: '—' }}</td>
                            @can('finance.manage')
                                <td class="px-4 py-3 text-right">
                                    <form action="{{ route('finance.loans.repayments.destroy', $repayment) }}" method="POST" onsubmit="return confirm('Delete this repayment?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-gray-400 hover:text-red-600 font-medium">Delete</button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">No repayments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @can('finance.manage')
            @unless ($loan->isSettled())
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-800 mb-4">Record Repayment</h3>
                    <form action="{{ route('finance.loans.repayments.store', $loan) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        @csrf
                        <div>
                            <label for="repayment_date" class="block text-sm font-medium text-gray-700">Date</label>
                            <input type="date" name="repayment_date" id="repayment_date" value="{{ old('repayment_date', date('Y-m-d')) }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                            @error('repayment_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="repayment_amount" class="block text-sm font-medium text-gray-700">Amount (Rp)</label>
                            <input type="number" step="0.01" min="0.01" max="{{ $loan->outstandingAmount() }}" name="amount" id="repayment_amount" value="{{ old('amount') }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                            @error('amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="repayment_account" class="block text-sm font-medium text-gray-700">Company Account</label>
                            <select name="account_id" id="repayment_account" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                @foreach (\App\Models\Account::where('is_active', true)->get() as $account)
                                    <option value="{{ $account->id }}" @selected(old('account_id', $loan->account_id) == $account->id)>{{ $account->name }}</option>
                                @endforeach
                            </select>
                            @error('account_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-3">
                            <label for="repayment_notes" class="block text-sm font-medium text-gray-700">Notes</label>
                            <input type="text" name="notes" id="repayment_notes" value="{{ old('notes') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                        </div>
                        <div class="sm:col-span-3">
                            <button type="submit" class="inline-flex items-center rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark">Record Repayment</button>
                        </div>
                    </form>
                </div>
            @endunless
        @endcan
    </div>
</x-layouts.erp>
