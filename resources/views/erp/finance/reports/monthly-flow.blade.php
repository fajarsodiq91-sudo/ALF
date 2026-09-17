<x-layouts.erp title="Monthly Cash Flow">
    <div class="max-w-4xl">
        <x-erp.flash />

        <div class="mb-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Income vs expenses by month.</p>
            </div>
            <a href="{{ route('finance.reports.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                ← Back to Reports
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            @if ($data->isEmpty())
                <div class="px-6 py-12 text-center text-gray-500">
                    No transactions
                </div>
            @else
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Month</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-500">Income</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-500">Expense</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-500">Net</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($data as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-gray-900 font-medium">{{ $row['month'] }}</td>
                                <td class="px-6 py-4 text-right font-semibold text-green-600">
                                    Rp {{ number_format($row['income'], 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-red-600">
                                    Rp {{ number_format($row['expense'], 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right font-semibold {{ $row['net'] >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                                    Rp {{ number_format($row['net'], 2, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-50 font-semibold">
                            <td class="px-6 py-4 text-gray-900">Total</td>
                            <td class="px-6 py-4 text-right text-green-600">
                                Rp {{ number_format($data->sum('income'), 2, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right text-red-600">
                                Rp {{ number_format($data->sum('expense'), 2, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right {{ $data->sum('net') >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                                Rp {{ number_format($data->sum('net'), 2, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-layouts.erp>
