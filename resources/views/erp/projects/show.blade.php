<x-layouts.erp :title="$project->name">
    <div class="max-w-5xl space-y-6">
        <x-erp.flash />

        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs text-gray-500">{{ $project->code }}</p>
                    <h2 class="text-lg font-semibold text-gray-800">{{ $project->name }}</h2>
                    @if ($project->description)
                        <p class="mt-1 text-sm text-gray-500">{{ $project->description }}</p>
                    @endif
                </div>
                @can('projects.manage')
                    <div class="flex items-center gap-3 shrink-0">
                        <a href="{{ route('projects.edit', $project) }}" class="text-sm font-medium text-brand hover:text-brand-dark">Edit</a>
                        <form action="{{ route('projects.destroy', $project) }}" method="POST" onsubmit="return confirm('Delete this project and all its tasks?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-medium text-gray-400 hover:text-red-600">Delete</button>
                        </form>
                    </div>
                @endcan
            </div>

            <dl class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div><dt class="text-gray-500">Status</dt><dd class="font-medium text-gray-800">{{ \App\Models\Project::STATUSES[$project->status] ?? $project->status }}</dd></div>
                <div><dt class="text-gray-500">Customer</dt><dd class="font-medium text-gray-800">{{ $project->customer ? $project->customer->customer_code.' — '.$project->customer->name : '—' }}</dd></div>
                <div><dt class="text-gray-500">Project Manager</dt><dd class="font-medium text-gray-800">{{ $project->projectManager?->name ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Contract Value</dt><dd class="font-medium text-gray-800">Rp {{ number_format((float) $project->contract_value, 0, ',', '.') }}</dd></div>
                <div><dt class="text-gray-500">Start</dt><dd class="font-medium text-gray-800">{{ $project->start_date?->format('d M Y') ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Deadline</dt><dd class="font-medium {{ $project->isOverdue() ? 'text-red-600' : 'text-gray-800' }}">{{ $project->end_date?->format('d M Y') ?? '—' }}@if ($project->isOverdue()) (overdue)@endif</dd></div>
                <div class="col-span-2">
                    <dt class="text-gray-500">Progress ({{ $project->done_tasks_count }}/{{ $project->tasks_count }} tasks done)</dt>
                    <dd class="mt-1 flex items-center gap-2">
                        <div class="h-2 flex-1 rounded-full bg-gray-200"><div class="h-2 rounded-full bg-gradient-to-br from-brand-light to-brand-dark" style="width: {{ $project->progressPercent() }}%"></div></div>
                        <span class="text-xs text-gray-600">{{ $project->progressPercent() }}%</span>
                    </dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg overflow-x-auto">
            <div class="px-4 py-3 border-b border-gray-200 text-sm font-semibold text-gray-800">Tasks</div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Task</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Assignee</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Due</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        @can('projects.manage')
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($project->tasks as $task)
                        <tr>
                            <td class="px-4 py-3 {{ $task->status === 'done' ? 'text-gray-400 line-through' : 'font-medium text-gray-800' }}">{{ $task->title }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $task->assignee?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $task->due_date?->format('d M Y') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @can('projects.manage')
                                    <form action="{{ route('projects.tasks.update', $task) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" onchange="this.form.submit()" class="rounded-md border-gray-300 py-1 text-xs shadow-sm focus:border-brand focus:ring-brand">
                                            @foreach (\App\Models\ProjectTask::STATUSES as $value => $label)
                                                <option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @else
                                    <span class="text-gray-500">{{ \App\Models\ProjectTask::STATUSES[$task->status] ?? $task->status }}</span>
                                @endcan
                            </td>
                            @can('projects.manage')
                                <td class="px-4 py-3 text-right">
                                    <form action="{{ route('projects.tasks.destroy', $task) }}" method="POST" class="inline" onsubmit="return confirm('Delete this task?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-gray-400 hover:text-red-600 font-medium">Delete</button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No tasks yet.</td></tr>
                    @endforelse
                </tbody>
            </table>

            @can('projects.manage')
                <form action="{{ route('projects.tasks.store', $project) }}" method="POST" class="flex flex-wrap items-start gap-3 border-t border-gray-200 bg-gray-50 px-4 py-3">
                    @csrf
                    <div>
                        <input type="text" name="title" value="{{ old('title') }}" placeholder="New task title" required
                               class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                        @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <select name="assignee_id" class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                        <option value="">Unassigned</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((int) old('assignee_id') === $employee->id)>{{ $employee->name }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="due_date" value="{{ old('due_date') }}" class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                    <button type="submit" class="rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">Add Task</button>
                </form>
            @endcan
        </div>

        <a href="{{ route('projects.index') }}" class="inline-block text-sm text-gray-500 hover:text-gray-700">&larr; Back to projects</a>
    </div>
</x-layouts.erp>
