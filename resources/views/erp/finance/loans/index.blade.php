<x-layouts.erp title="Loans">
    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">Interest-free loans between the owner and the company. Loans move cash between accounts but never count as income or expense.</p>
            @can('finance.manage')
                <a href="{{ route('finance.loans.create') }}" class="inline-flex items-center rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark">
                    Record Loan
                </a>
            @endcan
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                <p class="text-sm font-medium text-gray-600">Company owes the owner</p>
                <p class="mt-2 text-2xl font-bold text-orange-600">Rp {{ number_format($owedToOwner, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                <p class="text-sm font-medium text-gray-600">Owner owes the company</p>
                <p class="mt-2 text-2xl font-bold text-blue-600">Rp {{ number_format($owedByOwner, 2, ',', '.') }}</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Loan #</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Date</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Direction</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Party</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Account</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Amount</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Outstanding</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($loans as $loan)
                        <tr>
                            <td class="px-4 py-3 font-mono text-gray-600">{{ $loan->loan_number }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $loan->loan_date->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ \App\Models\Loan::DIRECTIONS[$loan->direction] }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ $loan->party_name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $loan->account->name }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">Rp {{ number_format($loan->amount, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">Rp {{ number_format($loan->outstandingAmount(), 0, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                @if ($loan->isSettled())
                                    <span class="inline-flex rounded-full bg-green-100 text-green-700 px-2 py-0.5 text-xs">Settled</span>
                                @else
                                    <span class="inline-flex rounded-full bg-amber-100 text-amber-700 px-2 py-0.5 text-xs">Open</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('finance.loans.show', $loan) }}" class="text-brand hover:text-brand-dark font-medium">Details</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-gray-400">No loans recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.erp>
