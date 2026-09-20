<x-layouts.erp title="Tax Summary">
    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="mb-4 flex items-center justify-between">
            <p class="text-sm text-gray-500">Tax owed, paid to the tax office, and still outstanding, by month.</p>
            <div class="flex items-center gap-4">
                <a href="{{ route('finance.tax-payments.create') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">Record Tax Payment</a>
                <a href="{{ route('finance.reports.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">← Back to Reports</a>
            </div>
        </div>

        @php $rp = fn ($v) => 'Rp ' . number_format($v, 2, ',', '.'); @endphp

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
            @if ($data->isEmpty())
                <div class="px-6 py-12 text-center text-gray-500">No taxed transactions</div>
            @else
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th rowspan="2" class="px-4 py-3 text-left font-medium text-gray-500 align-bottom">Month</th>
                            <th colspan="5" class="px-4 py-2 text-center font-medium text-gray-500 border-l border-gray-200">PPN (VAT)</th>
                            <th colspan="3" class="px-4 py-2 text-center font-medium text-gray-500 border-l border-gray-200">PPh withheld on expenses</th>
                            <th rowspan="2" class="px-4 py-3 text-right font-medium text-gray-500 align-bottom border-l border-gray-200">PPh withheld on income (credit)</th>
                        </tr>
                        <tr>
                            <th class="px-4 py-2 text-right font-medium text-gray-500 border-l border-gray-200">Output</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-500">Input</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-500">Payable</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-500">Paid</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-500">Outstanding</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-500 border-l border-gray-200">Owed</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-500">Paid</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-500">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($data as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-900 font-medium">{{ $row['month'] }}</td>
                                <td class="px-4 py-3 text-right border-l border-gray-100">{{ $rp($row['vat_out']) }}</td>
                                <td class="px-4 py-3 text-right">{{ $rp($row['vat_in']) }}</td>
                                <td class="px-4 py-3 text-right">{{ $rp($row['vat_payable']) }}</td>
                                <td class="px-4 py-3 text-right">{{ $rp($row['vat_paid']) }}</td>
                                <td class="px-4 py-3 text-right font-semibold {{ $row['vat_outstanding'] > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $rp($row['vat_outstanding']) }}</td>
                                <td class="px-4 py-3 text-right border-l border-gray-100">{{ $rp($row['wht_expense']) }}</td>
                                <td class="px-4 py-3 text-right">{{ $rp($row['wht_paid']) }}</td>
                                <td class="px-4 py-3 text-right font-semibold {{ $row['wht_outstanding'] > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $rp($row['wht_outstanding']) }}</td>
                                <td class="px-4 py-3 text-right border-l border-gray-100">{{ $rp($row['wht_income']) }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-50 font-semibold">
                            <td class="px-4 py-3 text-gray-900">Total</td>
                            <td class="px-4 py-3 text-right border-l border-gray-100">{{ $rp($data->sum('vat_out')) }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($data->sum('vat_in')) }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($data->sum('vat_payable')) }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($data->sum('vat_paid')) }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($data->sum('vat_outstanding')) }}</td>
                            <td class="px-4 py-3 text-right border-l border-gray-100">{{ $rp($data->sum('wht_expense')) }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($data->sum('wht_paid')) }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($data->sum('wht_outstanding')) }}</td>
                            <td class="px-4 py-3 text-right border-l border-gray-100">{{ $rp($data->sum('wht_income')) }}</td>
                        </tr>
                    </tbody>
                </table>
                <p class="px-6 py-3 text-xs text-gray-400">PPN Payable = Output − Input; a negative outstanding amount is a credit. PPh withheld on income is a prepaid tax credit and is not remitted by the company.</p>
            @endif
        </div>
    </div>
</x-layouts.erp>
