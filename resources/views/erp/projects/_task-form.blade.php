@php
    $task ??= null;
    $selectedAssignees = collect(old('assignee_ids', $task?->assignees->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id)->all();
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Title</label>
        <input type="text" name="title" value="{{ old('title', $task->title ?? '') }}" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Notes</label>
        <textarea name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('description', $task->description ?? '') }}</textarea>
        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Progress</label>
        <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            @foreach (\App\Models\ProjectTask::STATUSES as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $task->status ?? 'todo') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Priority</label>
        <select name="priority" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            @foreach (\App\Models\ProjectTask::PRIORITIES as $value => $label)
                <option value="{{ $value }}" @selected(old('priority', $task->priority ?? 'medium') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('priority') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Start date</label>
        <input type="date" name="start_date" value="{{ old('start_date', $task ? $task->start_date?->format('Y-m-d') : now()->format('Y-m-d')) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('start_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Due date</label>
        <input type="date" name="due_date" value="{{ old('due_date', $task ? $task->due_date?->format('Y-m-d') : now()->addDays(7)->format('Y-m-d')) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('due_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2" x-data="{ selected: @js($selectedAssignees) }">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-medium text-gray-700">Assign to</span>
            <div class="flex -space-x-2">
                @foreach ($employees as $employee)
                    <span x-show="selected.includes({{ $employee->id }})" x-cloak><x-erp.avatar :employee="$employee" size="h-7 w-7" class="ring-2 ring-white" /></span>
                @endforeach
            </div>
            <span class="text-sm font-normal text-gray-400">— assigned employees are notified by email</span>
        </div>
        <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-2 max-h-48 overflow-y-auto rounded-md border border-gray-200 p-3">
            @foreach ($employees as $employee)
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="assignee_ids[]" value="{{ $employee->id }}" x-model.number="selected" @checked(in_array($employee->id, $selectedAssignees, true)) class="rounded border-gray-300 text-brand focus:ring-brand">
                    <x-erp.avatar :employee="$employee" />
                    <span>{{ $employee->name }}@unless ($employee->email) <span class="text-xs text-amber-600">(no email)</span>@endunless</span>
                </label>
            @endforeach
        </div>
        @error('assignee_ids') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        @error('assignee_ids.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>
