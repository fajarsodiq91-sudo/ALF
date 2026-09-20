<?php

namespace App\Http\Requests\Finance;

use App\Rules\DifferentAccountsRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransferTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('finance.manage');
    }

    public function rules(): array
    {
        return [
            'transfer_date' => 'required|date',
            'amount' => 'required|decimal:0,2|min:0.01',
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_id' => ['required', 'exists:accounts,id', new DifferentAccountsRule()],
            'fee' => 'nullable|decimal:0,2|min:0',
            'description' => 'nullable|string|max:500',
        ];
    }
}
