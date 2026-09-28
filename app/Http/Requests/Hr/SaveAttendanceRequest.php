<?php

namespace App\Http\Requests\Hr;

use App\Models\AttendanceRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('hr.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(AttendanceRecord::STATUSES))],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i', 'after_or_equal:check_in'],
            'notes' => ['nullable', 'string', 'max:500'],
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

            $duplicate = AttendanceRecord::query()
                ->where('employee_id', $this->input('employee_id'))
                ->whereDate('attendance_date', $this->input('attendance_date'))
                ->when($this->route('attendance'), fn ($query, $current) => $query->whereKeyNot($current->getKey()))
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('attendance_date', 'Attendance for this employee on that date already exists.');
            }
        }];
    }
}
