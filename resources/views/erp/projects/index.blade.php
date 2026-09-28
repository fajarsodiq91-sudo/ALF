<x-layouts.erp title="Projects">
    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">
                Daftar proyek PT Alfajar Logic Futura.
                <span class="font-medium text-gray-800">{{ $projects->count() }}</span> proyek.
            </p>
            @can('projects.manage')
                <a href="{{ route('projects.create') }}" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">Add Project</a>
            @endcan
        </div>

        <form method="GET" action="{{ route('projects.index') }}" class="mb-4 flex flex-wrap gap-3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name or code"
                   class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                <option value="">All statuses</option>
                @foreach (\App\Models\Project::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <label class="inline-flex items-center gap-1.5 text-sm text-gray-600">
                <input type="checkbox" name="overdue" value="1" @checked(request()->boolean('overdue')) class="rounded border-gray-300 text-brand focus:ring-brand">
                Overdue only
            </label>
            <label class="inline-flex items-center gap-1.5 text-sm text-gray-600">
                <input type="checkbox" name="task_overdue" value="1" @checked(request()->boolean('task_overdue')) class="rounded border-gray-300 text-brand focus:ring-brand">
                Has overdue tasks
            </label>
            <button type="submit" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-all duration-150 hover:bg-gray-50 hover:shadow-md hover:-translate-y-px">Filter</button>
        </form>

        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Code</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Project</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Customer</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">PM</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Deadline</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Contract Value</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Progress</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($projects as $project)
                        <tr>
                            <td class="px-4 py-3 text-gray-500">{{ $project->code }}</td>
                            <td class="px-4 py-3"><a href="{{ route('projects.show', $project) }}" class="font-medium text-brand hover:text-brand-dark">{{ $project->name }}</a></td>
                            <td class="px-4 py-3 text-gray-500">{{ $project->customer ? $project->customer->customer_code.' — '.$project->customer->name : '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $project->projectManager?->name ?? '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap {{ $project->isOverdue() ? 'text-red-600 font-medium' : 'text-gray-500' }}">{{ $project->end_date?->format('d M Y') ?? '—' }}@if ($project->isOverdue()) (overdue)@endif</td>
                            <td class="px-4 py-3 text-right text-gray-800">Rp {{ number_format((float) $project->contract_value, 0, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 w-20 rounded-full bg-gray-200"><div class="h-1.5 rounded-full bg-gradient-to-br from-brand-light to-brand-dark" style="width: {{ $project->progressPercent() }}%"></div></div>
                                    <span class="text-xs text-gray-500">{{ $project->progressPercent() }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-blue-50 text-blue-700' => $project->status === 'planned',
                                    'bg-amber-50 text-amber-700' => in_array($project->status, ['in_progress', 'on_hold']),
                                    'bg-green-50 text-green-700' => $project->status === 'completed',
                                    'bg-gray-100 text-gray-500' => $project->status === 'cancelled',
                                ])>{{ \App\Models\Project::STATUSES[$project->status] ?? $project->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-6 text-center text-gray-400">No projects yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.erp>
