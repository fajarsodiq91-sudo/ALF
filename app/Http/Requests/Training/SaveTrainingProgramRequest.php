<?php

namespace App\Http\Requests\Training;

use App\Models\TrainingProgram;
use App\Services\MasterData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTrainingProgramRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'program_type' => ['required', Rule::in(MasterData::codes('program_type'))],
            'is_corporate' => ['sometimes', 'boolean'],
            'training_category_id' => ['nullable', 'exists:training_categories,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer', Rule::exists('training_program_images', 'id')->where('training_program_id', $this->route('program')?->id)],
            'duration_days' => ['required', 'integer', 'min:1', 'max:100'],
            'session_minutes' => ['nullable', 'integer', 'min:15', 'max:720'],
            'standard_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'discount_type' => ['nullable', Rule::in(array_keys(TrainingProgram::DISCOUNT_TYPES))],
            'discount_value' => [
                'nullable', 'required_with:discount_type', 'numeric', 'min:0.01',
                ...($this->input('discount_type') === TrainingProgram::DISCOUNT_PERCENTAGE ? ['max:100'] : []),
            ],
            'discount_expires_at' => ['nullable', 'required_with:discount_type', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'discount_value.required_with' => 'Enter the discount amount for this promo.',
            'discount_value.max' => 'A percentage discount cannot be more than 100%.',
            'discount_expires_at.required_with' => 'Set until when this promo is active.',
        ];
    }
}
