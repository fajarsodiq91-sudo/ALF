@php $record ??= null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div class="sm:col-span-2">
        <label for="employee_id" class="block text-sm font-medium text-gray-700">Employee</label>
        <select name="employee_id" id="employee_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <option value="">Select employee</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}" @selected((int) old('employee_id', $record->employee_id ?? '') === $employee->id)>{{ $employee->name }} ({{ $employee->employee_number }})</option>
            @endforeach
        </select>
        @error('employee_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="attendance_date" class="block text-sm font-medium text-gray-700">Date</label>
        <input type="date" name="attendance_date" id="attendance_date" value="{{ old('attendance_date', $record?->attendance_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('attendance_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
        <select name="status" id="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            @foreach (\App\Models\AttendanceRecord::STATUSES as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $record->status ?? 'present') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="check_in" class="block text-sm font-medium text-gray-700">Check In</label>
        <input type="time" name="check_in" id="check_in" value="{{ old('check_in', $record?->check_in ? substr($record->check_in, 0, 5) : '') }}"  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('check_in') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="check_out" class="block text-sm font-medium text-gray-700">Check Out</label>
        <input type="time" name="check_out" id="check_out" value="{{ old('check_out', $record?->check_out ? substr($record->check_out, 0, 5) : '') }}"  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('check_out') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
        <textarea name="notes" id="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('notes', $record->notes ?? '') }}</textarea>
        @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="inline-flex items-center rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark">{{ $record ? 'Update Attendance' : 'Save Attendance' }}</button>
    <a href="{{ route('hr.attendance.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
</div>
