<x-layouts.erp title="HR — Leave">
    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">Pengajuan dan persetujuan cuti karyawan.</p>
            @can('hr.manage')
                <a href="{{ route('hr.leaves.create') }}" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">New Leave Request</a>
            @endcan
        </div>

        <h3 class="text-sm font-semibold text-gray-800 mb-2">Annual leave balance {{ $year }}</h3>
        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg overflow-x-auto mb-6">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Employee</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Quota</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Used</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Remaining</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($employees as $employee)
                        @php $usedDays = (int) ($used[$employee->id] ?? 0); @endphp
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $employee->name }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ $employee->annual_leave_quota }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ $usedDays }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-800">{{ $employee->annual_leave_quota - $usedDays }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No employees yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form method="GET" action="{{ route('hr.leaves.index') }}" class="mb-4 flex gap-3">
            <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                <option value="">All statuses</option>
                @foreach (\App\Models\LeaveRequest::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-all duration-150 hover:bg-gray-50 hover:shadow-md hover:-translate-y-px">Filter</button>
        </form>

        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Employee</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Type</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Dates</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Days</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Reason</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        @can('hr.manage')
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($leaves as $leave)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $leave->employee->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ \App\Models\LeaveRequest::TYPES[$leave->leave_type] ?? $leave->leave_type }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $leave->start_date->format('d M Y') }} – {{ $leave->end_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ $leave->days }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $leave->reason ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-amber-50 text-amber-700' => $leave->status === 'pending',
                                    'bg-green-50 text-green-700' => $leave->status === 'approved',
                                    'bg-red-50 text-red-700' => $leave->status === 'rejected',
                                ])>{{ \App\Models\LeaveRequest::STATUSES[$leave->status] ?? $leave->status }}</span>
                            </td>
                            @can('hr.manage')
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    @if ($leave->isPending())
                                        <form action="{{ route('hr.leaves.approve', $leave) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-green-600 hover:text-green-800 font-medium">Approve</button>
                                        </form>
                                        <form action="{{ route('hr.leaves.reject', $leave) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="ml-3 text-amber-600 hover:text-amber-800 font-medium">Reject</button>
                                        </form>
                                    @endif
                                    <form action="{{ route('hr.leaves.destroy', $leave) }}" method="POST" class="inline" onsubmit="return confirm('Delete this leave request?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-gray-400 hover:text-red-600 font-medium">Delete</button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">No leave requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.erp>
