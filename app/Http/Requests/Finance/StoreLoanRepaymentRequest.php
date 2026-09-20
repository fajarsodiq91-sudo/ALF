<?php

namespace App\Http\Requests\Finance;

use App\Models\Loan;
use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRepaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('finance.manage');
    }

    public function rules(): array
    {
        /** @var Loan $loan */
        $loan = $this->route('loan');
        $outstanding = max($loan->outstandingAmount(), 0);

        return [
            'repayment_date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'amount' => ['required', 'decimal:0,2', 'min:0.01', "max:{$outstanding}"],
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return ['amount.max' => 'The repayment cannot exceed the outstanding balance of this loan.'];
    }
}
