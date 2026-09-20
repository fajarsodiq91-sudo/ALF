<?php

namespace App\Http\Requests\Finance;

use App\Models\Tax;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaxPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('finance.manage');
    }

    public function rules(): array
    {
        return [
            'payment_date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'tax_type' => ['required', Rule::in(array_keys(Tax::TYPES))],
            'period' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'amount' => 'required|decimal:0,2|min:0.01',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
