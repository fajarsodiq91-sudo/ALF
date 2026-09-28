<?php

namespace App\Http\Requests\Sales;

use App\Models\TrainingProgram;
use App\Services\MasterData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('sales.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'programs' => ['required', 'array', 'min:1'],
            'programs.*.training_program_id' => ['required', Rule::exists(TrainingProgram::class, 'id')->where('is_active', true)],
            'programs.*.delivery_mode' => ['required', Rule::in(MasterData::codes('delivery_mode'))],
            'programs.*.location' => ['nullable', 'string', 'max:255'],
            'programs.*.fee' => ['nullable', 'numeric', 'min:0'],
            'programs.*.meetings' => ['required', 'array', 'min:1'],
            'programs.*.meetings.*.meeting_date' => ['required', 'date'],
            'programs.*.meetings.*.start_time' => ['nullable', 'date_format:H:i'],
            'programs.*.meetings.*.end_time' => ['nullable', 'date_format:H:i', 'after:programs.*.meetings.*.start_time'],
            'programs.*.meetings.*.location' => ['nullable', 'string', 'max:255'],
            'programs.*.meetings.*.topic' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'programs.required' => 'Add at least one program before approving.',
            'programs.min' => 'Add at least one program before approving.',
            'programs.*.meetings.required' => 'Each program needs at least one meeting date.',
            'programs.*.meetings.min' => 'Each program needs at least one meeting date.',
        ];
    }
}
