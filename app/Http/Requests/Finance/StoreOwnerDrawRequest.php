<?php

namespace App\Http\Requests\Finance;

use App\Models\Tax;
use App\Services\TransactionProof;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOwnerDrawRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('finance.manage');
    }

    public function rules(): array
    {
        return [
            'draw_type' => ['required', Rule::in(['dividend', 'salary'])],
            'transaction_date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'payee' => 'required|string|max:255',
            'amount' => 'required|decimal:0,2|min:0.01',
            'tax_id' => ['required', Rule::exists('taxes', 'id')->where('type', Tax::TYPE_WITHHOLDING)],
            'tax_amount' => 'nullable|decimal:0,2|min:0|lte:amount',
            'notes' => 'nullable|string|max:500',
            ...TransactionProof::rules(),
        ];
    }
}
