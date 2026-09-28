<?php

namespace App\Http\Requests\Training;

use App\Models\Payroll;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayTrainingSessionPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('finance.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'account_id' => ['required', Rule::exists('accounts', 'id')->where('is_active', true)],
            'paid_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(Payroll::PAYMENT_METHODS)],
        ];
    }
}
