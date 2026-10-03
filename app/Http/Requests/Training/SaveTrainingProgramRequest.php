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

    /** Blank tier rows the admin left empty are ignored rather than rejected. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'group_tiers' => collect($this->input('group_tiers', []))
                ->filter(fn ($tier) => is_array($tier) && (filled($tier['min'] ?? null) || filled($tier['price'] ?? null)))
                ->values()
                ->all(),
        ]);
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
            'certificate_template_id' => ['nullable', 'exists:certificate_templates,id'],
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
            'group_max_size' => ['nullable', 'integer', 'min:1', 'max:50'],
            'group_tiers' => ['nullable', 'array', 'max:20'],
            'group_tiers.*.min' => ['required', 'integer', 'min:2', 'distinct', 'max:'.max(2, (int) $this->input('group_max_size', 2))],
            'group_tiers.*.price' => ['required', 'numeric', 'min:0'],
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
            'group_tiers.*.min.required' => 'Enter from how many people each group price applies.',
            'group_tiers.*.min.min' => 'A group price starts from 2 people (the standard price already covers 1 person).',
            'group_tiers.*.min.max' => 'A group price cannot start above the maximum group size.',
            'group_tiers.*.min.distinct' => 'Two group prices start from the same number of people.',
            'group_tiers.*.price.required' => 'Enter the price per person for each group tier.',
            'discount_value.required_with' => 'Enter the discount amount for this promo.',
            'discount_value.max' => 'A percentage discount cannot be more than 100%.',
            'discount_expires_at.required_with' => 'Set until when this promo is active.',
        ];
    }
}
