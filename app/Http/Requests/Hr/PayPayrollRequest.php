<?php

namespace App\Http\Requests\Hr;

use App\Models\Payroll;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayPayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('hr.payroll') && $this->user()->can('finance.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'account_id' => ['required', 'exists:accounts,id'],
            'paid_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(Payroll::PAYMENT_METHODS)],
        ];
    }
}
