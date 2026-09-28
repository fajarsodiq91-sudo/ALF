<x-layouts.erp title="Payslip">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg p-8">
            <div class="flex items-start justify-between border-b border-gray-200 pb-4 mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">Payslip</h2>
                    <p class="text-sm text-gray-500">PT Alfajar Logic Futura</p>
                </div>
                <p class="text-sm text-gray-500">Period: <span class="font-medium text-gray-800">{{ \Carbon\Carbon::createFromFormat('Y-m', $payroll->period)->format('F Y') }}</span></p>
            </div>

            <dl class="grid grid-cols-2 gap-y-2 text-sm mb-6">
                <dt class="text-gray-500">Employee</dt><dd class="text-gray-800 font-medium">{{ $payroll->employee->name }}</dd>
                <dt class="text-gray-500">Employee Number</dt><dd class="text-gray-800">{{ $payroll->employee->employee_number }}</dd>
                <dt class="text-gray-500">Position</dt><dd class="text-gray-800">{{ $payroll->employee->position }}</dd>
                <dt class="text-gray-500">Status</dt><dd class="text-gray-800">{{ $payroll->isPaid() ? 'Paid on '.$payroll->paid_date?->format('d M Y') : 'Draft' }}</dd>
            </dl>

            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-100">
                    <tr><td class="py-2 text-gray-500">Basic Salary</td><td class="py-2 text-right">Rp {{ number_format((float) $payroll->basic_salary, 0, ',', '.') }}</td></tr>
                    <tr><td class="py-2 text-gray-500">Allowances</td><td class="py-2 text-right">Rp {{ number_format((float) $payroll->allowances, 0, ',', '.') }}</td></tr>
                    <tr><td class="py-2 text-gray-500">Deductions</td><td class="py-2 text-right">− Rp {{ number_format((float) $payroll->deductions, 0, ',', '.') }}</td></tr>
                    <tr class="font-semibold"><td class="py-3 text-gray-800">Net Salary</td><td class="py-3 text-right text-gray-800">Rp {{ number_format((float) $payroll->net_salary, 0, ',', '.') }}</td></tr>
                </tbody>
            </table>

            @if ($payroll->notes)
                <p class="mt-4 text-sm text-gray-500">Notes: {{ $payroll->notes }}</p>
            @endif

            <div class="mt-6 flex items-center gap-3 print:hidden">
                <button type="button" onclick="window.print()" class="rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">Print</button>
                <a href="{{ route('hr.payroll.index', ['period' => $payroll->period]) }}" class="text-sm text-gray-500 hover:text-gray-700">Back</a>
            </div>
        </div>
    </div>
</x-layouts.erp>
