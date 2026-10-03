<x-layouts.erp title="Training — Sessions">
    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">
                Sesi pelatihan untuk customer. Total fee (tanpa yang dibatalkan):
                <span class="font-medium text-gray-800">Rp {{ number_format($totalFee, 0, ',', '.') }}</span>
            </p>
            @can('training.manage')
                <a href="{{ route('training.create') }}" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">Add Session</a>
            @endcan
        </div>

        @if (request()->boolean('ready_to_complete'))
            <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Showing sessions that have ended and still need to be marked as done.
            </div>
        @endif

        <form method="GET" action="{{ route('training.index') }}" class="mb-4 flex flex-wrap gap-3">
            <select name="program_id" class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                <option value="">All programs</option>
                @foreach ($programs as $program)
                    <option value="{{ $program->id }}" @selected((int) request('program_id') === $program->id)>{{ $program->name }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                <option value="">All statuses</option>
                @foreach (\App\Models\TrainingSession::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="payment" class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                <option value="">All payments</option>
                <option value="awaiting" @selected(request('payment') === 'awaiting')>Awaiting confirmation</option>
            </select>
            <button type="submit" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-all duration-150 hover:bg-gray-50 hover:shadow-md hover:-translate-y-px">Filter</button>
        </form>

        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Dates</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Program</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Customer</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Mode</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Instructor</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Participants</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Fee</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        @can('training.manage')
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($sessions as $session)
                        @php $readyToComplete = ! in_array($session->status, ['completed', 'cancelled'], true) && $session->end_date->isPast(); @endphp
                        <tr>
                            <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $session->start_date->format('d M Y') }}@if (! $session->start_date->isSameDay($session->end_date)) – {{ $session->end_date->format('d M Y') }}@endif</td>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $session->program->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $session->customer ? $session->customer->customer_code.' — '.$session->customer->name : 'Public batch' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ \App\Services\MasterData::label('delivery_mode', $session->delivery_mode) }}@if ($session->location) · {{ $session->location }}@endif</td>
                            <td class="px-4 py-3 text-gray-500">{{ $session->instructor?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ $session->participants_count }}</td>
                            <td class="px-4 py-3 text-right text-gray-800">Rp {{ number_format((float) $session->fee, 0, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-blue-50 text-blue-700' => $session->status === 'planned',
                                    'bg-amber-50 text-amber-700' => $session->status === 'ongoing',
                                    'bg-green-50 text-green-700' => $session->status === 'completed',
                                    'bg-gray-100 text-gray-500' => $session->status === 'cancelled',
                                ])>{{ \App\Models\TrainingSession::STATUSES[$session->status] ?? $session->status }}</span>
                                @if ($readyToComplete)
                                    <span class="mt-1 block text-xs font-medium text-amber-600">Ended — mark as done</span>
                                @endif
                            </td>
                            @can('training.manage')
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    @if ($readyToComplete)
                                        <form action="{{ route('training.complete', $session) }}" method="POST" class="inline" onsubmit="return confirm('Mark this session as done? Certificates will be issued if it is a learning program.');">
                                            @csrf
                                            <button type="submit" class="action-tip mx-0.5 inline-flex items-center justify-center rounded-md p-1.5 align-middle transition text-green-600 hover:bg-green-50 hover:text-green-800" data-tip="Mark as done" aria-label="Mark as done"><x-erp.action-icon name="done" /></button>
                                        </form>
                                    @endif
                                    <a href="{{ route('training.edit', array_filter(['session' => $session->id, 'focus' => request('focus')])) }}" class="action-tip mx-0.5 inline-flex items-center justify-center rounded-md p-1.5 align-middle transition text-brand hover:bg-brand-50 hover:text-brand-dark" data-tip="Edit" aria-label="Edit"><x-erp.action-icon name="edit" /></a>
                                    <form action="{{ route('training.destroy', $session) }}" method="POST" class="inline" onsubmit="return confirm('Delete this session?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-tip mx-0.5 inline-flex items-center justify-center rounded-md p-1.5 align-middle transition text-gray-400 hover:bg-red-50 hover:text-red-600" data-tip="Delete" aria-label="Delete"><x-erp.action-icon name="delete" /></button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-6 text-center text-gray-400">No training sessions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($sessions->hasPages())
            <div class="mt-4">
                {{ $sessions->links() }}
            </div>
        @endif
    </div>
</x-layouts.erp>
