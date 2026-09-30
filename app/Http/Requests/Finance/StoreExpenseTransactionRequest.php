<?php

namespace App\Http\Requests\Finance;

use App\Models\Tax;
use App\Rules\ExpenseCategoryRule;
use App\Services\TransactionProof;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('finance.manage');
    }

    public function rules(): array
    {
        return [
            'transaction_date' => 'required|date',
            'amount' => 'required|decimal:0,2|min:0.01',
            'tax_id' => ['nullable', Rule::exists('taxes', 'id')->where(fn ($query) => $query->where('type', '!=', Tax::TYPE_FINAL))],
            'account_id' => 'required|exists:accounts,id',
            'category_id' => ['required', 'exists:categories,id', new ExpenseCategoryRule],
            'payee' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'payment_method' => 'required|string|max:100',
            'notes' => 'nullable|string|max:500',
            ...TransactionProof::rules(),
        ];
    }
}
