<x-layouts.erp title="Edit Training Session">
    @include('erp.partials.operating-hours')
    @php $inputClass = 'block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm'; @endphp

    <div class="max-w-3xl space-y-6">
        <x-erp.flash />

        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg p-6">
            <form action="{{ route('training.update', $session) }}" method="POST">
                @csrf
                @method('PUT')
                @include('erp.training.sessions._form')
            </form>
        </div>

        <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-x-auto">
            <div class="px-4 py-3 border-b border-gray-200 text-sm font-semibold text-gray-800">Meeting schedule</div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Date</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Time</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Place</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Topic</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Realised</th>
                        @can('training.manage')
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($session->meetings as $meeting)
                        <tr>
                            <td class="px-4 py-3 text-gray-800">{{ $meeting->meeting_date->format('D, d M Y') }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $meeting->timeRange() ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $meeting->location ?: ($session->location ?: '—') }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $meeting->topic ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($meeting->is_completed)
                                    <span class="inline-flex rounded-full bg-green-50 text-green-700 px-2 py-0.5 text-xs font-medium">Done {{ $meeting->completed_at?->format('d M') }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 text-gray-500 px-2 py-0.5 text-xs font-medium">Upcoming</span>
                                @endif
                            </td>
                            @can('training.manage')
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <form action="{{ route('training.meetings.toggle', $meeting) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-brand hover:text-brand-dark font-medium">{{ $meeting->is_completed ? 'Undo' : 'Mark done' }}</button>
                                    </form>
                                    <form action="{{ route('training.meetings.destroy', $meeting) }}" method="POST" class="inline" onsubmit="return confirm('Delete this meeting?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-gray-400 hover:text-red-600 font-medium">Delete</button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">No meetings scheduled yet.</td></tr>
                    @endforelse
                </tbody>
            </table>

            @can('training.manage')
                <form action="{{ route('training.meetings.store', $session) }}" method="POST"
                      x-data="{ m: { meeting_date: @js(old('meeting_date', '')), start_time: @js(old('start_time', '')), end_time: @js(old('end_time', '')) }, hours: window.operatingHours }"
                      class="grid grid-cols-2 sm:grid-cols-6 gap-2 items-start border-t border-gray-200 bg-gray-50 p-4">
                    @csrf
                    <div class="col-span-2">
                        <input type="date" name="meeting_date" x-model="m.meeting_date" @change="syncSlot(m)" required class="{{ $inputClass }}">
                        <p x-show="hoursHint(m.meeting_date)" x-text="hoursHint(m.meeting_date)" x-cloak class="mt-1 text-xs text-red-600"></p>
                        @error('meeting_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2">
                        <select @change="pickSlot(m, $event.target.value)" :required="hours.enforced" class="{{ $inputClass }}">
                            <option value="">Select time slot</option>
                            <template x-for="slot in slotsFor(m.meeting_date)" :key="slot.value">
                                <option :value="slot.value" :selected="slot.start === m.start_time && slot.end === m.end_time" x-text="slot.label"></option>
                            </template>
                        </select>
                    </div>
                    <input type="time" name="start_time" x-model="m.start_time" x-show="!hours.enforced" class="{{ $inputClass }}">
                    <div x-show="!hours.enforced">
                        <input type="time" name="end_time" x-model="m.end_time" class="{{ $inputClass }}">
                        @error('end_time') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <template x-if="hours.enforced">
                        <div class="hidden">
                            <input type="hidden" name="start_time" :value="m.start_time">
                            <input type="hidden" name="end_time" :value="m.end_time">
                        </div>
                    </template>
                    <input type="text" name="location" value="{{ old('location') }}" placeholder="Place" class="col-span-2 sm:col-span-3 {{ $inputClass }}">
                    <input type="text" name="topic" value="{{ old('topic') }}" placeholder="Topic" class="col-span-2 sm:col-span-3 {{ $inputClass }}">
                    <div class="col-span-2 sm:col-span-6">
                        <button type="submit" class="rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white shadow-sm hover:from-brand-dark hover:to-brand-dark">Add Meeting</button>
                    </div>
                </form>
            @endcan
        </div>
    </div>
</x-layouts.erp>
