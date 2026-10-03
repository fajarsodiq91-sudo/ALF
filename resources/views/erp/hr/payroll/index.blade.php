<x-layouts.erp title="HR — Payroll">
    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">
                Total gaji bersih {{ $period ? 'periode ini' : 'ditampilkan' }}:
                <span class="font-medium text-gray-800">Rp {{ number_format($totalNet, 0, ',', '.') }}</span>
            </p>
            <a href="{{ route('hr.payroll.create', ['period' => $period ?? now()->format('Y-m')]) }}" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">New Payroll</a>
        </div>

        <form method="GET" action="{{ route('hr.payroll.index') }}" class="mb-4 flex flex-wrap gap-3">
            <input type="month" name="period" value="{{ $period }}" placeholder="All periods" class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                <option value="">All statuses</option>
                <option value="{{ \App\Models\Payroll::DRAFT }}" @selected(request('status') === \App\Models\Payroll::DRAFT)>Draft</option>
                <option value="{{ \App\Models\Payroll::PAID }}" @selected(request('status') === \App\Models\Payroll::PAID)>Paid</option>
            </select>
            <button type="submit" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-all duration-150 hover:bg-gray-50 hover:shadow-md hover:-translate-y-px">Filter</button>
        </form>
        @if (! $period)
            <p class="mb-4 text-xs text-gray-400">Showing all periods.</p>
        @endif

        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg overflow-x-auto">
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
                                <a href="{{ route('hr.payroll.show', $payroll) }}" class="action-tip mx-0.5 inline-flex items-center justify-center rounded-md p-1.5 align-middle transition text-gray-500 hover:bg-gray-100 hover:text-gray-800" data-tip="Slip" aria-label="Slip"><x-erp.action-icon name="slip" /></a>
                                @if ($payroll->isPaid())
                                    @can('finance.manage')
                                        <form action="{{ route('hr.payroll.cancel-payment', $payroll) }}" method="POST" class="inline" onsubmit="return confirm('Cancel this payment? The Finance expense will be removed.');">
                                            @csrf
                                            <button type="submit" class="action-tip mx-0.5 inline-flex items-center justify-center rounded-md p-1.5 align-middle transition text-amber-600 hover:bg-amber-50 hover:text-amber-800" data-tip="Cancel Payment" aria-label="Cancel Payment"><x-erp.action-icon name="cancel" /></button>
                                        </form>
                                    @endcan
                                @else
                                    @can('finance.manage')
                                        <button type="button" @click="paying = !paying" class="action-tip mx-0.5 inline-flex items-center justify-center rounded-md p-1.5 align-middle transition text-green-600 hover:bg-green-50 hover:text-green-800" data-tip="Pay" aria-label="Pay"><x-erp.action-icon name="pay" /></button>
                                    @endcan
                                    <a href="{{ route('hr.payroll.edit', $payroll) }}" class="action-tip mx-0.5 inline-flex items-center justify-center rounded-md p-1.5 align-middle transition text-brand hover:bg-brand-50 hover:text-brand-dark" data-tip="Edit" aria-label="Edit"><x-erp.action-icon name="edit" /></a>
                                    <form action="{{ route('hr.payroll.destroy', $payroll) }}" method="POST" class="inline" onsubmit="return confirm('Delete this payroll?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-tip mx-0.5 inline-flex items-center justify-center rounded-md p-1.5 align-middle transition text-gray-400 hover:bg-red-50 hover:text-red-600" data-tip="Delete" aria-label="Delete"><x-erp.action-icon name="delete" /></button>
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
                                            <button type="submit" class="rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">Confirm Payment</button>
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

        @if ($payrolls->hasPages())
            <div class="mt-4">
                {{ $payrolls->links() }}
            </div>
        @endif
    </div>
</x-layouts.erp>
