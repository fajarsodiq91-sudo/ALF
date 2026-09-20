<x-layouts.erp title="Tax Payments">
    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">Tax remitted to the tax office. Each payment leaves the chosen account and is recorded as an expense under "Tax Payments".</p>
            @can('finance.manage')
                <a href="{{ route('finance.tax-payments.create') }}" class="inline-flex items-center rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark">
                    Record Tax Payment
                </a>
            @endcan
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Payment #</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Date</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Tax</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Period</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Account</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Reference</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Amount</th>
                        @can('finance.manage')
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="px-4 py-3 font-mono text-gray-600">{{ $payment->payment_number }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $payment->payment_date->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ $payment->tax_type === 'vat' ? 'PPN / VAT' : 'PPh / Withholding' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $payment->period }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ $payment->account->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $payment->reference ?: '—' }}</td>
                            <td class="px-4 py-3 text-right font-medium text-red-700">-Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                            @can('finance.manage')
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('finance.tax-payments.edit', $payment) }}" class="text-brand hover:text-brand-dark font-medium">Edit</a>
                                    <form action="{{ route('finance.tax-payments.destroy', $payment) }}" method="POST" class="inline" onsubmit="return confirm('Delete this tax payment?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-gray-400 hover:text-red-600 font-medium">Delete</button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-gray-400">No tax payments recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($payments->hasPages())
            <div class="mt-4">{{ $payments->links() }}</div>
        @endif
    </div>
</x-layouts.erp>
