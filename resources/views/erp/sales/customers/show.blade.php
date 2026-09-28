<x-layouts.erp :title="$customer->name ?? 'Customer'">
    <div class="max-w-5xl space-y-6">
        <x-erp.flash />

        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    @if ($customer->photoUrl())
                        <img src="{{ $customer->photoUrl() }}" alt="" class="h-16 w-16 rounded-full object-cover ring-1 ring-gray-200">
                    @endif
                    <div>
                        <p class="font-mono text-xs text-gray-500">{{ $customer->customer_code ?? 'No ID yet' }}</p>
                        <h2 class="text-lg font-semibold text-gray-800">{{ $customer->name ?? 'Waiting for customer to fill in' }}</h2>
                        <p class="text-sm text-gray-500">{{ \App\Services\MasterData::label('customer_type', $customer->customer_type) }}
                            @if ($customer->isPendingApproval()) · <span class="text-amber-700 font-medium">Pending approval</span>
                            @elseif ($customer->isRejected()) · <span class="text-red-600 font-medium">Rejected</span>
                            @elseif ($customer->approved_at) · approved {{ $customer->approved_at->format('d M Y') }}
                            @endif
                        </p>
                    </div>
                </div>
                @can('sales.manage')
                    <div class="flex items-center gap-3 shrink-0">
                        @if ($customer->isPendingApproval())
                            <a href="{{ route('sales.review', $customer) }}" class="rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-3 py-1.5 text-sm font-medium text-white shadow-sm">Review</a>
                        @endif
                        <a href="{{ route('sales.edit', $customer) }}" class="text-sm font-medium text-brand hover:text-brand-dark">Edit</a>
                    </div>
                @endcan
            </div>

            <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div><dt class="text-gray-500">Email</dt><dd class="text-gray-800">{{ $customer->email ?: '—' }}</dd></div>
                <div><dt class="text-gray-500">Phone</dt><dd class="text-gray-800">{{ $customer->phone ?: '—' }}</dd></div>
                <div><dt class="text-gray-500">City</dt><dd class="text-gray-800">{{ $customer->city ?: '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-gray-500">Address</dt><dd class="text-gray-800">{{ $customer->address ?: '—' }}</dd></div>
                @if ($customer->isRejected() && $customer->rejection_reason)
                    <div class="sm:col-span-2"><dt class="text-gray-500">Rejection reason</dt><dd class="text-gray-800">{{ $customer->rejection_reason }}</dd></div>
                @endif
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-x-auto">
            <div class="px-4 py-3 border-b border-gray-200 text-sm font-semibold text-gray-800">Programs</div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Program</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Dates</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Meetings done</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        @can('training.view')
                            <th class="px-4 py-3 text-right font-medium text-gray-500"></th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($customer->sessions as $session)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $session->program->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $session->start_date->format('d M Y') }} – {{ $session->end_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $session->meetings->where('is_completed', true)->count() }} / {{ $session->meetings->count() }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ \App\Models\TrainingSession::STATUSES[$session->status] ?? $session->status }}</td>
                            @can('training.view')
                                <td class="px-4 py-3 text-right"><a href="{{ route('training.edit', $session) }}" class="text-brand hover:text-brand-dark font-medium">Manage</a></td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No programs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-x-auto">
            <div class="px-4 py-3 border-b border-gray-200 text-sm font-semibold text-gray-800">Projects uploaded by the customer</div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Project</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Program</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Uploaded</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Access</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Portfolio</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($customer->projects as $project)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ $project->title }}</div>
                                @if ($project->description)<div class="text-xs text-gray-500">{{ $project->description }}</div>@endif
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $project->session?->program->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $project->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3 space-x-3">
                                @if ($project->file_path)
                                    <a href="{{ route('sales.projects.download', $project) }}" class="text-brand hover:text-brand-dark font-medium">Download file</a>
                                @endif
                                @if ($project->external_url)
                                    <a href="{{ $project->external_url }}" target="_blank" rel="noopener noreferrer" class="text-brand hover:text-brand-dark font-medium">Open link</a>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @can('sales.manage')
                                    <form action="{{ route('sales.projects.portfolio', $project) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" @class([
                                            'rounded-full px-2 py-0.5 text-xs font-medium',
                                            'bg-green-50 text-green-700' => $project->in_portfolio,
                                            'bg-gray-100 text-gray-500 hover:bg-gray-200' => ! $project->in_portfolio,
                                        ])>{{ $project->in_portfolio ? 'In portfolio' : 'Mark as added' }}</button>
                                    </form>
                                @else
                                    <span class="text-gray-500">{{ $project->in_portfolio ? 'In portfolio' : '—' }}</span>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">Nothing uploaded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <a href="{{ route('sales.index') }}" class="inline-block text-sm text-gray-500 hover:text-gray-700">&larr; Back to customers</a>
    </div>
</x-layouts.erp>
