<x-layouts.erp title="Transaction Ledger">
    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">Combined view of all income, expense, transfer, and loan transactions.</p>
        </div>

        @if ($transactions->isEmpty())
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-6 py-12 text-center">
                <p class="text-gray-500">No transactions yet</p>
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Date</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Number</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Type</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Account(s)</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Description</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Amount</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($transactions as $transaction)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-gray-900">
                                    {{ $transaction['date']->format('Y-m-d') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-gray-700">
                                    {{ $transaction['number'] }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($transaction['type'] === 'income')
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Income</span>
                                    @elseif ($transaction['type'] === 'expense')
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Expense</span>
                                    @elseif ($transaction['type'] === 'loan')
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800">Loan</span>
                                    @elseif ($transaction['type'] === 'repayment')
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800">Repayment</span>
                                    @else
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Transfer</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    @if ($transaction['type'] === 'transfer')
                                        {{ $transaction['from_account'] ?? 'N/A' }} <span class="text-gray-400">→</span> {{ $transaction['to_account'] ?? 'N/A' }}
                                    @else
                                        {{ $transaction['account'] ?? 'N/A' }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700 max-w-xs truncate">
                                    @if ($transaction['type'] === 'transfer')
                                        {{ $transaction['description'] ?? '-' }}
                                    @elseif (in_array($transaction['type'], ['loan', 'repayment']))
                                        {{ $transaction['description'] }}
                                    @else
                                        {{ $transaction['category'] ?? '-' }} • {{ $transaction['description'] }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right font-semibold">
                                    @if ($transaction['type'] === 'income')
                                        <span class="text-green-600">+Rp {{ number_format($transaction['amount'], 2, ',', '.') }}</span>
                                    @elseif ($transaction['type'] === 'expense')
                                        <span class="text-red-600">-Rp {{ number_format($transaction['amount'], 2, ',', '.') }}</span>
                                    @elseif (in_array($transaction['type'], ['loan', 'repayment']))
                                        <span class="{{ $transaction['flow'] > 0 ? 'text-green-600' : 'text-red-600' }}">{{ $transaction['flow'] > 0 ? '+' : '-' }}Rp {{ number_format($transaction['amount'], 2, ',', '.') }}</span>
                                    @else
                                        <span class="text-gray-600">Rp {{ number_format($transaction['amount'], 2, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $transaction['created_by'] ?? 'System' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.erp>
