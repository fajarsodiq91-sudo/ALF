<x-layouts.erp title="Profit & Loss">
    <div class="max-w-5xl">
        <x-erp.flash />

        <div class="mb-4 flex items-center justify-between">
            <form method="GET" class="flex items-center gap-2 text-sm text-gray-600">
                <label for="year">Year</label>
                <input type="number" name="year" id="year" value="{{ $year }}" min="2000" max="2100"
                       class="w-24 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand text-sm">
                <button type="submit" class="text-sm font-medium text-blue-600 hover:text-blue-700">Show</button>
            </form>
            <a href="{{ route('finance.reports.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">← Back to Reports</a>
        </div>

        @php $rp = fn ($v) => 'Rp ' . number_format($v, 2, ',', '.'); @endphp

        <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-x-auto">
            @if ($data->isEmpty())
                <div class="px-6 py-12 text-center text-gray-500">No transactions in {{ $year }}</div>
            @else
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Month</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Revenue</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Operating costs</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">PPh Final</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Profit after tax</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Dividends (gross)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($data as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $row['month'] }}</td>
                                <td class="px-4 py-3 text-right">{{ $rp($row['revenue']) }}</td>
                                <td class="px-4 py-3 text-right">{{ $rp($row['operating']) }}</td>
                                <td class="px-4 py-3 text-right">{{ $rp($row['final_tax']) }}</td>
                                <td class="px-4 py-3 text-right font-semibold {{ $row['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $rp($row['profit']) }}</td>
                                <td class="px-4 py-3 text-right text-gray-500">{{ $rp($row['dividends']) }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-50 font-semibold">
                            <td class="px-4 py-3 text-gray-900">Total</td>
                            <td class="px-4 py-3 text-right">{{ $rp($data->sum('revenue')) }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($data->sum('operating')) }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($data->sum('final_tax')) }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($data->sum('profit')) }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($data->sum('dividends')) }}</td>
                        </tr>
                        <tr class="font-semibold">
                            <td class="px-4 py-3 text-gray-900" colspan="4">Retained after dividends</td>
                            <td class="px-4 py-3 text-right {{ $data->sum('profit') - $data->sum('dividends') >= 0 ? 'text-green-600' : 'text-red-600' }}" colspan="2">{{ $rp($data->sum('profit') - $data->sum('dividends')) }}</td>
                        </tr>
                    </tbody>
                </table>
                <p class="px-6 py-3 text-xs text-gray-400">Revenue is before tax. Operating costs include owner salary at its gross amount; tax payments are excluded because they settle taxes already counted here or withheld from salary and dividends. PPh Final is accrued on income when it is recorded. Dividends are a distribution of profit, not a cost.</p>
            @endif
        </div>
    </div>
</x-layouts.erp>
