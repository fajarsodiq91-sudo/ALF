<x-layouts.erp title="HR — Employees">
    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">
                Daftar karyawan PT Alfajar Logic Futura.
                <span class="font-medium text-gray-800">{{ $employees->total() }}</span> karyawan.
            </p>
            @can('hr.manage')
                <a href="{{ route('hr.create') }}" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">
                    Add Employee
                </a>
            @endcan
        </div>

        <form method="GET" action="{{ route('hr.index') }}" class="mb-4 flex flex-wrap gap-3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name, number, position, or department"
                   class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <select name="type" class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                <option value="">All employment types</option>
                @foreach (\App\Services\MasterData::options('employment_type', request('type')) as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                <option value="">All statuses</option>
                @foreach (\App\Models\Employee::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-all duration-150 hover:bg-gray-50 hover:shadow-md hover:-translate-y-px">Filter</button>
        </form>

        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Number</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Position</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Department</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Type</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Joined</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        @can('hr.manage')
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($employees as $employee)
                        <tr>
                            <td class="px-4 py-3 text-gray-500">{{ $employee->employee_number }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $employee->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $employee->position }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $employee->department ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ \App\Services\MasterData::label('employment_type', $employee->employment_type) }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $employee->join_date?->format('d M Y') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-green-50 text-green-700' => $employee->status === 'active',
                                    'bg-amber-50 text-amber-700' => $employee->status === 'on_leave',
                                    'bg-gray-100 text-gray-500' => $employee->status === 'resigned',
                                ])>{{ \App\Models\Employee::STATUSES[$employee->status] ?? $employee->status }}</span>
                            </td>
                            @can('hr.manage')
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('hr.edit', $employee) }}" class="action-tip mx-0.5 inline-flex items-center justify-center rounded-md p-1.5 align-middle transition text-brand hover:bg-brand-50 hover:text-brand-dark" data-tip="Edit" aria-label="Edit"><x-erp.action-icon name="edit" /></a>
                                    <form action="{{ route('hr.destroy', $employee) }}" method="POST" class="inline" onsubmit="return confirm('Delete this employee?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-tip mx-0.5 inline-flex items-center justify-center rounded-md p-1.5 align-middle transition text-gray-400 hover:bg-red-50 hover:text-red-600" data-tip="Delete" aria-label="Delete"><x-erp.action-icon name="delete" /></button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-gray-400">No employees yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($employees->hasPages())
            <div class="mt-4">
                {{ $employees->links() }}
            </div>
        @endif
    </div>
</x-layouts.erp>
