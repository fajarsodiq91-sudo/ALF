<?php

namespace App\Http\Requests\Finance;

use App\Models\Loan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('finance.manage');
    }

    public function rules(): array
    {
        return [
            'direction' => ['required', Rule::in(array_keys(Loan::DIRECTIONS))],
            'loan_date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'party_name' => 'required|string|max:255',
            'amount' => 'required|decimal:0,2|min:0.01',
            'description' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
