<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveOperatingHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('masterdata.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'hours' => ['nullable', 'array'],
            'hours.*' => ['array'],
            'hours.*.*.start' => ['required', 'date_format:H:i'],
            'hours.*.*.end' => ['required', 'date_format:H:i', 'after:hours.*.*.start'],
            'hours.*.*.corporate' => ['sometimes', 'boolean'],
            'enforced' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hours.*.*.start.required' => 'Every time slot needs a start time.',
            'hours.*.*.end.required' => 'Every time slot needs an end time.',
            'hours.*.*.end.after' => 'A slot must end after it starts.',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            foreach ($this->input('hours', []) as $day => $slots) {
                if (! in_array((int) $day, range(1, 7), true)) {
                    $validator->errors()->add('hours', 'Unknown weekday.');

                    return;
                }
            }
        }];
    }
}
