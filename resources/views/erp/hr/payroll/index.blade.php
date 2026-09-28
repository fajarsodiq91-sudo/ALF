<x-layouts.erp title="HR — Payroll">
    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">
                Total gaji bersih periode ini:
                <span class="font-medium text-gray-800">Rp {{ number_format($totalNet, 0, ',', '.') }}</span>
            </p>
            <a href="{{ route('hr.payroll.create', ['period' => $period]) }}" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark">New Payroll</a>
        </div>

        <form method="GET" action="{{ route('hr.payroll.index') }}" class="mb-4 flex gap-3">
            <input type="month" name="period" value="{{ $period }}" class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <button type="submit" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Filter</button>
        </form>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Employee</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Basic</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Allowances</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Deductions</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Net</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                @forelse ($payrolls as $payroll)
                    <tbody x-data="{ paying: false }" class="divide-y divide-gray-100 border-t border-gray-100">
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $payroll->employee->name }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">Rp {{ number_format((float) $payroll->basic_salary, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">Rp {{ number_format((float) $payroll->allowances, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">Rp {{ number_format((float) $payroll->deductions, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-800">Rp {{ number_format((float) $payroll->net_salary, 0, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                @if ($payroll->isPaid())
                                    <span class="inline-flex rounded-full bg-green-50 text-green-700 px-2 py-0.5 text-xs font-medium">Paid {{ $payroll->paid_date?->format('d M') }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-amber-50 text-amber-700 px-2 py-0.5 text-xs font-medium">Draft</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('hr.payroll.show', $payroll) }}" class="text-gray-500 hover:text-gray-800 font-medium">Slip</a>
                                @if ($payroll->isPaid())
                                    @can('finance.manage')
                                        <form action="{{ route('hr.payroll.cancel-payment', $payroll) }}" method="POST" class="inline" onsubmit="return confirm('Cancel this payment? The Finance expense will be removed.');">
                                            @csrf
                                            <button type="submit" class="ml-3 text-amber-600 hover:text-amber-800 font-medium">Cancel Payment</button>
                                        </form>
                                    @endcan
                                @else
                                    @can('finance.manage')
                                        <button type="button" @click="paying = !paying" class="ml-3 text-green-600 hover:text-green-800 font-medium">Pay</button>
                                    @endcan
                                    <a href="{{ route('hr.payroll.edit', $payroll) }}" class="ml-3 text-brand hover:text-brand-dark font-medium">Edit</a>
                                    <form action="{{ route('hr.payroll.destroy', $payroll) }}" method="POST" class="inline" onsubmit="return confirm('Delete this payroll?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-gray-400 hover:text-red-600 font-medium">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @if (! $payroll->isPaid())
                            @can('finance.manage')
                                <tr x-show="paying" x-cloak class="bg-gray-50">
                                    <td colspan="7" class="px-4 py-3">
                                        <form action="{{ route('hr.payroll.pay', $payroll) }}" method="POST" class="flex flex-wrap items-end gap-3">
                                            @csrf
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600">Pay from account</label>
                                                <select name="account_id" required class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                                    @foreach ($accounts as $account)
                                                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600">Paid date</label>
                                                <input type="date" name="paid_date" value="{{ now()->format('Y-m-d') }}" required class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600">Method</label>
                                                <select name="payment_method" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                                    @foreach (\App\Models\Payroll::PAYMENT_METHODS as $method)
                                                        <option value="{{ $method }}">{{ $method }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <button type="submit" class="rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark">Confirm Payment</button>
                                        </form>
                                    </td>
                                </tr>
                            @endcan
                        @endif
                    </tbody>
                @empty
                    <tbody>
                        <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">No payroll for this period.</td></tr>
                    </tbody>
                @endforelse
            </table>
        </div>
    </div>
</x-layouts.erp>
