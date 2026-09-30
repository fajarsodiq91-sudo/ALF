<?php

namespace App\Services;

use App\Mail\ProjectTaskNotification;
use App\Models\Employee;
use App\Models\ProjectTask;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Emails the employees on a task whenever it is created or changed. A failed send never blocks the save. */
class ProjectTaskNotifier
{
    /** @param  list<int>  $assigneeIds */
    public static function created(ProjectTask $task, array $assigneeIds): void
    {
        $task->load('project');

        foreach (Employee::whereIn('id', $assigneeIds)->get() as $employee) {
            self::send($task, $employee, ProjectTaskNotification::ASSIGNED);
        }
    }

    /**
     * @param  list<int>  $previousAssigneeIds
     * @param  list<int>  $currentAssigneeIds
     * @param  array<string, array{0: string, 1: string}>  $changes
     */
    public static function updated(ProjectTask $task, array $previousAssigneeIds, array $currentAssigneeIds, array $changes): void
    {
        if ($changes === []) {
            return;
        }

        $task->load('project');

        $added = array_diff($currentAssigneeIds, $previousAssigneeIds);
        $removed = array_diff($previousAssigneeIds, $currentAssigneeIds);

        foreach (Employee::whereIn('id', [...$currentAssigneeIds, ...$removed])->get() as $employee) {
            $type = match (true) {
                in_array($employee->id, $removed, true) => ProjectTaskNotification::UNASSIGNED,
                in_array($employee->id, $added, true) => ProjectTaskNotification::ASSIGNED,
                default => ProjectTaskNotification::UPDATED,
            };

            self::send($task, $employee, $type, $type === ProjectTaskNotification::UPDATED ? $changes : []);
        }
    }

    /** @param  array<string, array{0: string, 1: string}>  $changes */
    private static function send(ProjectTask $task, Employee $employee, string $type, array $changes = []): void
    {
        if (! $employee->email) {
            return;
        }

        try {
            Mail::to($employee->email)->send(new ProjectTaskNotification($task, $employee, $type, $changes));
        } catch (Throwable $exception) {
            Log::error('Could not send the task notification.', ['task_id' => $task->id, 'employee_id' => $employee->id, 'error' => $exception->getMessage()]);
        }
    }
}
