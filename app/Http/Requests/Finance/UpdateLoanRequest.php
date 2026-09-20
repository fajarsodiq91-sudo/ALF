<?php

namespace App\Http\Requests\Finance;

use App\Models\Loan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('finance.manage');
    }

    public function rules(): array
    {
        /** @var Loan $loan */
        $loan = $this->route('loan');
        $repaid = max((float) $loan->repayments()->sum('amount'), 0.01);

        return [
            'direction' => ['required', Rule::in(array_keys(Loan::DIRECTIONS)), Rule::in([$loan->direction])],
            'loan_date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'party_name' => 'required|string|max:255',
            'amount' => ['required', 'decimal:0,2', "min:{$repaid}"],
            'description' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'direction.in' => 'The direction of an existing loan cannot be changed.',
            'amount.min' => 'The amount cannot be lower than what has already been repaid.',
        ];
    }
}
