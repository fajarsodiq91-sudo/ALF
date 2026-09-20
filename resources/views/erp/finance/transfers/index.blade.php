<x-layouts.erp title="Transfers">
    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">Move funds between accounts. Transfers do not affect income or expense totals.</p>
            @can('finance.manage')
                <a href="{{ route('finance.transfers.create') }}" class="inline-flex items-center rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark">
                    Record Transfer
                </a>
            @endcan
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Transfer #</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Date</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">From Account</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">To Account</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Amount</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Admin Fee</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Description</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">By</th>
                        @can('finance.manage')
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($transfers as $transfer)
                        <tr>
                            <td class="px-4 py-3 font-mono text-gray-600">{{ $transfer->transfer_number }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $transfer->transfer_date->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ $transfer->fromAccount->name }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ $transfer->toAccount->name }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-700">Rp {{ number_format($transfer->amount, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ $transfer->feeExpense ? 'Rp ' . number_format($transfer->feeExpense->amount, 0, ',', '.') : '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 truncate">{{ $transfer->description ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $transfer->createdBy->name }}</td>
                            @can('finance.manage')
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('finance.transfers.edit', $transfer) }}" class="text-brand hover:text-brand-dark font-medium">Edit</a>
                                    <form action="{{ route('finance.transfers.destroy', $transfer) }}" method="POST" class="inline" onsubmit="return confirm('Delete this transfer?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-gray-400 hover:text-red-600 font-medium">Delete</button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-gray-400">No transfers recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($transfers->hasPages())
            <div class="mt-4">
                {{ $transfers->links() }}
            </div>
        @endif
    </div>
</x-layouts.erp>
