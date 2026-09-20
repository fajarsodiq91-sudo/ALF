<x-layouts.erp title="Expenses">
    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">Record expense transactions with auto-numbered invoices.</p>
            @can('finance.manage')
                <a href="{{ route('finance.expenses.create') }}" class="inline-flex items-center rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark">
                    Record Expense
                </a>
            @endcan
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Transaction #</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Date</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Account</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Category</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Description</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Subtotal</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Tax</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Total</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">By</th>
                        @can('finance.manage')
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td class="px-4 py-3 font-mono text-gray-600">{{ $transaction->transaction_number }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $transaction->transaction_date->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ $transaction->account->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $transaction->category->name }}</td>
                            <td class="px-4 py-3 text-gray-500 truncate">{{ $transaction->description ?: '—' }}</td>
                            <td class="px-4 py-3 text-right text-gray-600">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">
                                @if ($transaction->tax)
                                    {{ $transaction->tax->name }}<br>
                                    <span class="text-xs">{{ $transaction->tax->type === 'vat' ? '+' : '−' }}Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-red-700">-Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $transaction->createdBy->name }}</td>
                            @can('finance.manage')
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('finance.expenses.edit', $transaction) }}" class="text-brand hover:text-brand-dark font-medium">Edit</a>
                                    <form action="{{ route('finance.expenses.destroy', $transaction) }}" method="POST" class="inline" onsubmit="return confirm('Delete this expense record?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-gray-400 hover:text-red-600 font-medium">Delete</button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-6 text-center text-gray-400">No expenses recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($transactions->hasPages())
            <div class="mt-4">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</x-layouts.erp>
