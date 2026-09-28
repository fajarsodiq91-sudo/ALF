<?php

namespace App\Http\Requests\Hr;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\MasterData;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SubmitLeaveRequest extends FormRequest
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
            'leave_type' => ['required', Rule::in(MasterData::codes('leave_type'))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
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

            $start = Carbon::parse($this->input('start_date'));
            $days = LeaveRequest::countWorkingDays($start, Carbon::parse($this->input('end_date')));

            if ($days === 0) {
                $validator->errors()->add('start_date', 'The selected dates contain no working days.');

                return;
            }

            if ($this->input('leave_type') === LeaveRequest::ANNUAL) {
                $remaining = Employee::findOrFail($this->input('employee_id'))->annualLeaveRemaining($start->year);

                if ($days > $remaining) {
                    $validator->errors()->add('leave_type', "Annual leave balance is not enough ({$remaining} day(s) left, {$days} requested).");
                }
            }
        }];
    }
}
