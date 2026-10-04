<x-layouts.erp title="Attendance Report">
    <div class="max-w-5xl space-y-4">
        <x-erp.flash />

        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-gray-800">{{ $session->program->name }}</h1>
                <p class="text-sm text-gray-500">
                    {{ $session->customer->name }} ·
                    <span class="font-medium text-gray-800">{{ $heldCount }}</span> of {{ $meetings->count() }} meetings held
                </p>
            </div>
            <a href="{{ route('training.edit', $session) }}" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">&larr; Back to session</a>
        </div>

        <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Participant</th>
                        @foreach ($meetings as $meeting)
                            <th class="px-3 py-3 text-center font-medium text-gray-500 whitespace-nowrap">
                                {{ $meeting->meeting_date->format('d M') }}
                                <span class="block text-xs font-normal text-gray-400">{{ $meeting->start_time ? substr($meeting->start_time, 0, 5) : '' }}</span>
                            </th>
                        @endforeach
                        <th class="px-4 py-3 text-center font-medium text-gray-500">Present</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-500">Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-800">{{ $row['customer']->name }}</span>
                                <span class="block font-mono text-xs text-gray-400">{{ $row['customer']->customer_code }}</span>
                            </td>
                            @foreach ($meetings as $meeting)
                                @php $attendance = $row['attendances']->get($meeting->id); @endphp
                                <td class="px-3 py-3 text-center whitespace-nowrap">
                                    @can('training.manage')
                                        <form action="{{ route('training.attendance.toggle', [$meeting, $row['customer']]) }}" method="POST" class="inline" @if ($attendance) onsubmit="return confirm('Remove this attendance?');" @endif>
                                            @csrf @method('PATCH')
                                            <button type="submit" class="rounded px-1.5 py-0.5 hover:bg-gray-100" title="{{ $attendance ? 'Click to remove' : 'Click to mark present' }}">
                                                @if ($attendance)
                                                    <span class="font-semibold text-green-600">&#10003;</span>
                                                    <span class="block text-xs text-gray-400">{{ $attendance->checked_in_at->format('H:i') }}{{ $attendance->method === 'manual' ? ' · manual' : '' }}</span>
                                                @else
                                                    <span class="text-gray-300">—</span>
                                                @endif
                                            </button>
                                        </form>
                                    @else
                                        @if ($attendance)
                                            <span class="font-semibold text-green-600">&#10003;</span>
                                            <span class="block text-xs text-gray-400">{{ $attendance->checked_in_at->format('H:i') }}</span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    @endcan
                                </td>
                            @endforeach
                            <td class="px-4 py-3 text-center text-gray-800">{{ $row['present'] }}/{{ $heldCount }}</td>
                            <td class="px-4 py-3 text-center">
                                @if ($row['percent'] === null)
                                    <span class="text-gray-400">—</span>
                                @else
                                    <span @class([
                                        'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                        'bg-green-50 text-green-700' => $row['percent'] >= 80,
                                        'bg-amber-50 text-amber-700' => $row['percent'] >= 50 && $row['percent'] < 80,
                                        'bg-red-50 text-red-700' => $row['percent'] < 50,
                                    ])>{{ $row['percent'] }}%</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $meetings->count() + 3 }}" class="px-4 py-6 text-center text-gray-400">No participants yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="text-xs text-gray-500">The rate counts only meetings that have been held (past, today, or marked done). Click a cell to mark someone present by hand or undo it.</p>
    </div>
</x-layouts.erp>
