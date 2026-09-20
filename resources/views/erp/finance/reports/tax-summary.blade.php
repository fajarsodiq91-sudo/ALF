<x-layouts.erp title="Tax Summary">
    <div class="max-w-5xl">
        <x-erp.flash />

        <div class="mb-4 flex items-center justify-between">
            <p class="text-sm text-gray-500">PPN output (on income) vs input (on expenses), and PPh withheld, by month.</p>
            <a href="{{ route('finance.reports.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                ← Back to Reports
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
            @if ($data->isEmpty())
                <div class="px-6 py-12 text-center text-gray-500">No taxed transactions</div>
            @else
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Month</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-500">PPN Output</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-500">PPN Input</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-500">PPN Payable</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-500">PPh Withheld on Income</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-500">PPh Withheld on Expenses</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($data as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-gray-900 font-medium">{{ $row['month'] }}</td>
                                <td class="px-6 py-4 text-right">Rp {{ number_format($row['vat_out'], 2, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right">Rp {{ number_format($row['vat_in'], 2, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right font-semibold {{ $row['vat_payable'] >= 0 ? 'text-red-600' : 'text-green-600' }}">Rp {{ number_format($row['vat_payable'], 2, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right">Rp {{ number_format($row['wht_income'], 2, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right">Rp {{ number_format($row['wht_expense'], 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-50 font-semibold">
                            <td class="px-6 py-4 text-gray-900">Total</td>
                            <td class="px-6 py-4 text-right">Rp {{ number_format($data->sum('vat_out'), 2, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right">Rp {{ number_format($data->sum('vat_in'), 2, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right">Rp {{ number_format($data->sum('vat_payable'), 2, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right">Rp {{ number_format($data->sum('wht_income'), 2, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right">Rp {{ number_format($data->sum('wht_expense'), 2, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
                <p class="px-6 py-3 text-xs text-gray-400">PPN Payable = Output − Input (negative means credit). PPh on income is a prepaid tax credit; PPh on expenses is owed to the tax office.</p>
            @endif
        </div>
    </div>
</x-layouts.erp>
