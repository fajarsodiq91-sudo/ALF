<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\SaveProjectTaskRequest;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Services\ProjectTaskNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectTaskController extends Controller
{
    public function store(SaveProjectTaskRequest $request, Project $project): RedirectResponse
    {
        $assigneeIds = $this->assigneeIds($request);
        $task = $project->tasks()->create($request->safe()->except(['assignee_ids']));
        $task->assignees()->sync($assigneeIds);

        ProjectTaskNotifier::created($task, $assigneeIds);

        return redirect()->route('projects.show', $project)->with('status', 'Task added.');
    }

    public function edit(ProjectTask $task): View
    {
        $this->authorize('projects.manage');

        return view('erp.projects.task-edit', [
            'task' => $task->load(['project', 'assignees']),
            'employees' => Employee::where('status', '!=', 'resigned')->orderBy('name')->get(),
        ]);
    }

    public function update(SaveProjectTaskRequest $request, ProjectTask $task): RedirectResponse
    {
        $assigneeIds = $this->assigneeIds($request);
        $previousAssigneeIds = $task->assignees()->pluck('employees.id')->all();

        $task->fill($request->safe()->except(['assignee_ids']));
        $changes = $this->changes($task, $previousAssigneeIds, $assigneeIds);
        $task->save();
        $task->assignees()->sync($assigneeIds);

        ProjectTaskNotifier::updated($task, $previousAssigneeIds, $assigneeIds, $changes);

        return redirect()->route('projects.show', $task->project_id)->with('status', 'Task updated.');
    }

    public function updateStatus(Request $request, ProjectTask $task): RedirectResponse
    {
        $this->authorize('projects.manage');

        $data = $request->validate(['status' => ['required', Rule::in(array_keys(ProjectTask::STATUSES))]]);

        $task->fill($data);
        $assigneeIds = $task->assignees()->pluck('employees.id')->all();
        $changes = $this->changes($task, $assigneeIds, $assigneeIds);
        $task->save();

        ProjectTaskNotifier::updated($task, $assigneeIds, $assigneeIds, $changes);

        return redirect()->route('projects.show', $task->project_id)->with('status', 'Task updated.');
    }

    public function destroy(ProjectTask $task): RedirectResponse
    {
        $this->authorize('projects.manage');

        $task->delete();

        return redirect()->route('projects.show', $task->project_id)->with('status', 'Task deleted.');
    }

    /** @return list<int> */
    private function assigneeIds(SaveProjectTaskRequest $request): array
    {
        return array_map('intval', $request->validated('assignee_ids') ?? []);
    }

    /**
     * Human-readable before/after pairs for the fields that differ on the (unsaved) task.
     *
     * @param  list<int>  $previousAssigneeIds
     * @param  list<int>  $currentAssigneeIds
     * @return array<string, array{0: string, 1: string}>
     */
    private function changes(ProjectTask $task, array $previousAssigneeIds, array $currentAssigneeIds): array
    {
        $format = fn (string $field, mixed $value): string => match ($field) {
            'status' => ProjectTask::STATUSES[$value] ?? (string) $value,
            'priority' => ProjectTask::PRIORITIES[$value] ?? (string) $value,
            'start_date', 'due_date' => $value ? Carbon::parse($value)->format('d M Y') : '—',
            default => $value !== null && $value !== '' ? (string) $value : '—',
        };

        $labels = [
            'title' => 'Title', 'description' => 'Description', 'status' => 'Status',
            'priority' => 'Priority', 'start_date' => 'Start date', 'due_date' => 'Due date',
        ];

        $changes = [];
        foreach ($labels as $field => $label) {
            if ($task->isDirty($field)) {
                $changes[$label] = [$format($field, $task->getOriginal($field)), $format($field, $task->getAttribute($field))];
            }
        }

        if (array_diff($previousAssigneeIds, $currentAssigneeIds) || array_diff($currentAssigneeIds, $previousAssigneeIds)) {
            $names = fn (array $ids): string => Employee::whereIn('id', $ids)->orderBy('name')->pluck('name')->implode(', ') ?: '—';
            $changes['Assignees'] = [$names($previousAssigneeIds), $names($currentAssigneeIds)];
        }

        return $changes;
    }
}
