<x-layouts.erp title="New Leave Request">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('hr.leaves.store') }}" method="POST">
                @csrf
<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div class="sm:col-span-2">
        <label for="employee_id" class="block text-sm font-medium text-gray-700">Employee</label>
        <select name="employee_id" id="employee_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <option value="">Select employee</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}" @selected((int) old('employee_id', '') === $employee->id)>{{ $employee->name }} ({{ $employee->employee_number }})</option>
            @endforeach
        </select>
        @error('employee_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="leave_type" class="block text-sm font-medium text-gray-700">Leave Type</label>
        <select name="leave_type" id="leave_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <option value="">Select type</option>
            @foreach (\App\Models\LeaveRequest::TYPES as $value => $label)
                <option value="{{ $value }}" @selected(old('leave_type', '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('leave_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div></div>
    <div class="">
        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
        <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}" required  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('start_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
        <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}" required  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('end_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label for="reason" class="block text-sm font-medium text-gray-700">Reason</label>
        <textarea name="reason" id="reason" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('reason') }}</textarea>
        @error('reason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>
<p class="mt-3 text-xs text-gray-500">Days are counted Monday–Friday. Annual leave is checked against the employee's remaining balance for the year.</p>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="inline-flex items-center rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark">Submit Request</button>
    <a href="{{ route('hr.leaves.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
</div>

            </form>
        </div>
    </div>
</x-layouts.erp>
