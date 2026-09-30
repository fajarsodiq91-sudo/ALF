<?php

namespace App\Http\Requests\Settings;

use App\Services\BookedSlots;
use App\Services\OperatingHours;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BlockSlotRequest extends FormRequest
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
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'reason' => ['nullable', 'string', 'max:255'],
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

            if (! OperatingHours::windowContaining($this->input('date'), $this->input('start_time'), $this->input('end_time'), true)) {
                $validator->errors()->add('date', 'The time must be inside the operating hours of that day.');
            } elseif (BookedSlots::conflicts($this->input('date'), $this->input('start_time'), $this->input('end_time'))) {
                $validator->errors()->add('date', 'That time overlaps something already booked.');
            }
        }];
    }
}
