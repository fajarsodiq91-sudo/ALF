<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\SaveProjectRequest;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $projects = Project::query()
            ->with(['customer', 'projectManager'])
            ->withCount(['tasks', 'tasks as done_tasks_count' => fn ($query) => $query->where('status', 'done')])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->boolean('overdue'), fn ($query) => $query->where('end_date', '<', now())->whereNotIn('status', ['completed', 'cancelled']))
            ->when($request->boolean('task_overdue'), fn ($query) => $query->whereHas('tasks', fn ($task) => $task->where('due_date', '<', now())->where('status', '!=', 'done')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(fn ($inner) => $inner->where('name', 'like', $term)->orWhere('code', 'like', $term));
            })
            ->latest('start_date')
            ->get();

        return view('erp.projects.index', ['projects' => $projects]);
    }

    public function create(): View
    {
        $this->authorize('projects.manage');

        return view('erp.projects.create', [
            ...$this->formData(),
            'suggestedCode' => 'PRJ-'.now()->format('Y').'-'.str_pad((string) (Project::count() + 1), 3, '0', STR_PAD_LEFT),
        ]);
    }

    public function store(SaveProjectRequest $request): RedirectResponse
    {
        $project = Project::create($request->validated());

        return redirect()->route('projects.show', $project)->with('status', 'Project created successfully.');
    }

    public function show(Project $project): View
    {
        $project->load(['customer', 'projectManager', 'tasks.assignee']);
        $project->loadCount(['tasks', 'tasks as done_tasks_count' => fn ($query) => $query->where('status', 'done')]);

        return view('erp.projects.show', [
            'project' => $project,
            'employees' => Employee::where('status', '!=', 'resigned')->orderBy('name')->get(),
        ]);
    }

    public function edit(Project $project): View
    {
        $this->authorize('projects.manage');

        return view('erp.projects.edit', [...$this->formData(), 'project' => $project]);
    }

    public function update(SaveProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return redirect()->route('projects.show', $project)->with('status', 'Project updated successfully.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('projects.manage');

        $project->delete();

        return redirect()->route('projects.index')->with('status', 'Project deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'customers' => Customer::registered()->orderBy('name')->get(),
            'managers' => Employee::where('status', '!=', 'resigned')->orderBy('name')->get(),
        ];
    }
}
