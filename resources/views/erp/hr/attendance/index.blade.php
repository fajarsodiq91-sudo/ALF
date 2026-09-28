<x-layouts.erp title="HR — Attendance">
    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">Catatan kehadiran harian karyawan.</p>
            @can('hr.manage')
                <a href="{{ route('hr.attendance.create') }}" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark">Add Attendance</a>
            @endcan
        </div>

        <form method="GET" action="{{ route('hr.attendance.index') }}" class="mb-4 flex flex-wrap gap-3">
            <input type="month" name="month" value="{{ $month }}" class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <select name="employee_id" class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                <option value="">All employees</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected((int) request('employee_id') === $employee->id)>{{ $employee->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Filter</button>
        </form>

        <h3 class="text-sm font-semibold text-gray-800 mb-2">Monthly recap</h3>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto mb-6">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Employee</th>
                        @foreach (\App\Models\AttendanceRecord::STATUSES as $label)
                            <th class="px-4 py-3 text-right font-medium text-gray-500">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($recap as $employeeId => $counts)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $records->firstWhere('employee_id', $employeeId)->employee->name }}</td>
                            @foreach (\App\Models\AttendanceRecord::STATUSES as $value => $label)
                                <td class="px-4 py-3 text-right text-gray-500">{{ $counts[$value] ?? 0 }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count(\App\Models\AttendanceRecord::STATUSES) + 1 }}" class="px-4 py-6 text-center text-gray-400">No attendance recorded for this month.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Date</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Employee</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Check In</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Check Out</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Notes</th>
                        @can('hr.manage')
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($records as $record)
                        <tr>
                            <td class="px-4 py-3 text-gray-500">{{ $record->attendance_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $record->employee->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ \App\Models\AttendanceRecord::STATUSES[$record->status] ?? $record->status }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $record->check_in ? substr($record->check_in, 0, 5) : '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $record->check_out ? substr($record->check_out, 0, 5) : '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $record->notes ?: '—' }}</td>
                            @can('hr.manage')
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('hr.attendance.edit', $record) }}" class="text-brand hover:text-brand-dark font-medium">Edit</a>
                                    <form action="{{ route('hr.attendance.destroy', $record) }}" method="POST" class="inline" onsubmit="return confirm('Delete this record?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-gray-400 hover:text-red-600 font-medium">Delete</button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">No records.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.erp>
