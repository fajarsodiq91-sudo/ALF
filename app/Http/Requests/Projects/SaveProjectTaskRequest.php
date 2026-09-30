<?php

namespace App\Http\Requests\Projects;

use App\Models\ProjectTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveProjectTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('projects.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['sometimes', Rule::in(array_keys(ProjectTask::STATUSES))],
            'priority' => ['required', Rule::in(array_keys(ProjectTask::PRIORITIES))],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'assignee_ids' => ['nullable', 'array'],
            'assignee_ids.*' => ['integer', 'distinct', 'exists:employees,id'],
        ];
    }
}
