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
            'end_time' => ['required', 'date_format:H:i'],
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

            $isSlot = collect(OperatingHours::slotsForDate($this->input('date')))
                ->contains(fn ($slot) => $slot[0] === $this->input('start_time') && $slot[1] === $this->input('end_time'));

            if (! $isSlot) {
                $validator->errors()->add('date', 'That is not an operating-hours slot.');
            } elseif (BookedSlots::conflicts($this->input('date'), $this->input('start_time'), $this->input('end_time'))) {
                $validator->errors()->add('date', 'That slot is already booked.');
            }
        }];
    }
}
