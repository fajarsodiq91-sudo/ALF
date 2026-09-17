<x-layouts.erp title="Expense by Category">
    <div class="max-w-4xl">
        <x-erp.flash />

        <div class="mb-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Breakdown of expenses by category.</p>
            </div>
            <a href="{{ route('finance.reports.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                ← Back to Reports
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            @if ($data->isEmpty())
                <div class="px-6 py-12 text-center text-gray-500">
                    No expense transactions
                </div>
            @else
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Category</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-500">Count</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-500">Total</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-500">Average</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($data as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-gray-900 font-medium">{{ $row['category'] }}</td>
                                <td class="px-6 py-4 text-right text-gray-700">{{ $row['count'] }}</td>
                                <td class="px-6 py-4 text-right font-semibold text-red-600">
                                    Rp {{ number_format($row['total'], 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right text-gray-700">
                                    Rp {{ number_format($row['total'] / $row['count'], 2, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-50 font-semibold">
                            <td class="px-6 py-4 text-gray-900">Total</td>
                            <td class="px-6 py-4 text-right text-gray-700">{{ $data->sum('count') }}</td>
                            <td class="px-6 py-4 text-right text-red-600">
                                Rp {{ number_format($data->sum('total'), 2, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right text-gray-700">
                                Rp {{ number_format($data->sum('total') / $data->sum('count'), 2, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-layouts.erp>
