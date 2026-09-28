@php $payroll ??= null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div class="sm:col-span-2">
        <label for="employee_id" class="block text-sm font-medium text-gray-700">Employee</label>
        <select name="employee_id" id="employee_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <option value="">Select employee</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}" @selected((int) old('employee_id', $payroll->employee_id ?? '') === $employee->id)>{{ $employee->name }} ({{ $employee->employee_number }})</option>
            @endforeach
        </select>
        @error('employee_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="period" class="block text-sm font-medium text-gray-700">Period</label>
        <input type="month" name="period" id="period" value="{{ old('period', $payroll->period ?? $period ?? now()->format('Y-m')) }}" required  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('period') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div></div>
    <div class="">
        <label for="basic_salary" class="block text-sm font-medium text-gray-700">Basic Salary (Rp)</label>
        <input type="number" name="basic_salary" id="basic_salary" value="{{ old('basic_salary', $payroll->basic_salary ?? '') }}" required step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('basic_salary') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="allowances" class="block text-sm font-medium text-gray-700">Allowances (Rp)</label>
        <input type="number" name="allowances" id="allowances" value="{{ old('allowances', $payroll->allowances ?? 0) }}" step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('allowances') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="deductions" class="block text-sm font-medium text-gray-700">Deductions (Rp)</label>
        <input type="number" name="deductions" id="deductions" value="{{ old('deductions', $payroll->deductions ?? 0) }}" step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('deductions') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
        <textarea name="notes" id="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('notes', $payroll->notes ?? '') }}</textarea>
        @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>
<p class="mt-3 text-xs text-gray-500">Net salary = basic salary + allowances − deductions.</p>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="inline-flex items-center rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark">{{ $payroll ? 'Update Payroll' : 'Create Payroll' }}</button>
    <a href="{{ route('hr.payroll.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
</div>
