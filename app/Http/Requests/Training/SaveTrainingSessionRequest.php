<?php

namespace App\Http\Requests\Training;

use App\Models\TrainingSession;
use App\Services\MasterData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTrainingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('training.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'training_program_id' => ['required', 'exists:training_programs,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'instructor_id' => ['nullable', 'exists:employees,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'delivery_mode' => ['required', Rule::in(MasterData::codes('delivery_mode'))],
            'location' => ['nullable', 'string', 'max:255'],
            'participants_count' => ['required', 'integer', 'min:0'],
            'fee' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(array_keys(TrainingSession::STATUSES))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
