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

        @php
            $priorityStyles = ['low' => 'bg-gray-100 text-gray-600', 'medium' => 'bg-blue-100 text-blue-700', 'high' => 'bg-amber-100 text-amber-700', 'urgent' => 'bg-red-100 text-red-700'];
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            @foreach (\App\Models\ProjectTask::STATUSES as $statusValue => $statusLabel)
                @php $columnTasks = $project->tasks->where('status', $statusValue); @endphp
                <div class="rounded-lg bg-gray-100 p-3">
                    <div class="mb-3 flex items-center justify-between px-1 text-sm font-semibold text-gray-700">
                        <span>{{ $statusLabel }}</span>
                        <span class="rounded-full bg-white px-2 py-0.5 text-xs text-gray-500">{{ $columnTasks->count() }}</span>
                    </div>
                    <div class="space-y-3">
                        @forelse ($columnTasks as $task)
                            <div class="rounded-md border border-gray-200 bg-white p-3 shadow-sm">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-medium {{ $task->status === 'done' ? 'text-gray-400 line-through' : 'text-gray-800' }}">{{ $task->title }}</p>
                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium {{ $priorityStyles[$task->priority] ?? $priorityStyles['medium'] }}">{{ \App\Models\ProjectTask::PRIORITIES[$task->priority] ?? $task->priority }}</span>
                                </div>
                                @if ($task->description)
                                    <p class="mt-1 text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($task->description, 100) }}</p>
                                @endif
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @forelse ($task->assignees as $assignee)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-brand/10 py-0.5 pl-0.5 pr-2 text-xs text-gray-700" title="{{ $assignee->email ?? 'No email' }}"><x-erp.avatar :employee="$assignee" size="h-5 w-5" />{{ $assignee->name }}</span>
                                    @empty
                                        <span class="text-xs text-gray-400">Unassigned</span>
                                    @endforelse
                                </div>
                                <p class="mt-2 text-xs {{ $task->isOverdue() ? 'font-medium text-red-600' : 'text-gray-500' }}">
                                    @if ($task->start_date || $task->due_date)
                                        {{ $task->start_date?->format('d M') ?? '…' }} → {{ $task->due_date?->format('d M Y') ?? '…' }}@if ($task->isOverdue()) (overdue)@endif
                                    @else
                                        No dates
                                    @endif
                                </p>
                                @can('projects.manage')
                                    <div class="mt-3 flex items-center justify-between gap-2 border-t border-gray-100 pt-2">
                                        <form action="{{ route('projects.tasks.status', $task) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <select name="status" onchange="this.form.submit()" class="rounded-md border-gray-300 py-1 text-xs shadow-sm focus:border-brand focus:ring-brand">
                                                @foreach (\App\Models\ProjectTask::STATUSES as $value => $label)
                                                    <option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                        <div class="flex items-center gap-3 text-xs font-medium">
                                            <a href="{{ route('projects.tasks.edit', $task) }}" class="text-brand hover:text-brand-dark">Edit</a>
                                            <form action="{{ route('projects.tasks.destroy', $task) }}" method="POST" onsubmit="return confirm('Delete this task?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-gray-400 hover:text-red-600">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                @endcan
                            </div>
                        @empty
                            <p class="px-1 py-4 text-center text-xs text-gray-400">No tasks.</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>

        @can('projects.manage')
            <details class="bg-white rounded-lg shadow-md border border-gray-200 p-4" {{ $errors->any() ? 'open' : '' }}>
                <summary class="cursor-pointer text-sm font-semibold text-gray-800">+ Add task</summary>
                <form action="{{ route('projects.tasks.store', $project) }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    @include('erp.projects._task-form', ['task' => null])
                    <button type="submit" class="rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">Add task &amp; notify assignees</button>
                </form>
            </details>
        @endcan

        <a href="{{ route('projects.index') }}" class="inline-block text-sm text-gray-500 hover:text-gray-700">&larr; Back to projects</a>
    </div>
</x-layouts.erp>
