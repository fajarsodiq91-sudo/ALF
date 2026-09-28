@php $employee ??= null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div>
        <label for="employee_number" class="block text-sm font-medium text-gray-700">Employee Number</label>
        <input type="text" name="employee_number" id="employee_number" value="{{ old('employee_number', $employee->employee_number ?? '') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('employee_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
        <input type="text" name="name" id="name" value="{{ old('name', $employee->name ?? '') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="position" class="block text-sm font-medium text-gray-700">Position</label>
        <input type="text" name="position" id="position" value="{{ old('position', $employee->position ?? '') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('position') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="department" class="block text-sm font-medium text-gray-700">Department</label>
        <input type="text" name="department" id="department" value="{{ old('department', $employee->department ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('department') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="employment_type" class="block text-sm font-medium text-gray-700">Employment Type</label>
        <select name="employment_type" id="employment_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            @foreach (\App\Services\MasterData::options('employment_type', $employee->employment_type ?? null) as $value => $label)
                <option value="{{ $value }}" @selected(old('employment_type', $employee->employment_type ?? 'permanent') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('employment_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
        <select name="status" id="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            @foreach (\App\Models\Employee::STATUSES as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $employee->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="join_date" class="block text-sm font-medium text-gray-700">Join Date</label>
        <input type="date" name="join_date" id="join_date" value="{{ old('join_date', $employee?->join_date?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('join_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" name="email" id="email" value="{{ old('email', $employee->email ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
        <input type="text" name="phone" id="phone" value="{{ old('phone', $employee->phone ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="annual_leave_quota" class="block text-sm font-medium text-gray-700">Annual Leave Quota (days)</label>
        <input type="number" min="0" max="365" name="annual_leave_quota" id="annual_leave_quota" value="{{ old('annual_leave_quota', $employee->annual_leave_quota ?? 12) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('annual_leave_quota') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
        <textarea name="address" id="address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('address', $employee->address ?? '') }}</textarea>
        @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
        <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('notes', $employee->notes ?? '') }}</textarea>
        @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">
        {{ $employee ? 'Update Employee' : 'Create Employee' }}
    </button>
    <a href="{{ route('hr.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
</div>
