<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\StoreProjectTaskRequest;
use App\Models\Project;
use App\Models\ProjectTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectTaskController extends Controller
{
    public function store(StoreProjectTaskRequest $request, Project $project): RedirectResponse
    {
        $project->tasks()->create($request->validated());

        return redirect()->route('projects.show', $project)->with('status', 'Task added.');
    }

    public function update(Request $request, ProjectTask $task): RedirectResponse
    {
        $this->authorize('projects.manage');

        $data = $request->validate(['status' => ['required', Rule::in(array_keys(ProjectTask::STATUSES))]]);
        $task->update($data);

        return redirect()->route('projects.show', $task->project_id)->with('status', 'Task updated.');
    }

    public function destroy(ProjectTask $task): RedirectResponse
    {
        $this->authorize('projects.manage');

        $task->delete();

        return redirect()->route('projects.show', $task->project_id)->with('status', 'Task deleted.');
    }
}
