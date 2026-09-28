<?php

namespace App\Http\Requests\Hr;

use App\Models\Payroll;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SavePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('hr.payroll');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'period' => [
                'required', 'date_format:Y-m',
                Rule::unique('payrolls')
                    ->where('employee_id', $this->input('employee_id'))
                    ->ignore($this->route('payroll')),
            ],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'allowances' => ['nullable', 'numeric', 'min:0'],
            'deductions' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['period.unique' => 'A payroll for this employee and period already exists.'];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isEmpty()
                && Payroll::netFor($this->input('basic_salary'), $this->input('allowances', 0), $this->input('deductions', 0)) <= 0) {
                $validator->errors()->add('deductions', 'Net salary must be greater than zero.');
            }
        }];
    }

    /**
     * @return array<string, mixed>
     */
    public function payrollData(): array
    {
        $data = $this->validated();
        $data['allowances'] = $data['allowances'] ?? 0;
        $data['deductions'] = $data['deductions'] ?? 0;
        $data['net_salary'] = Payroll::netFor($data['basic_salary'], $data['allowances'], $data['deductions']);

        return $data;
    }
}
