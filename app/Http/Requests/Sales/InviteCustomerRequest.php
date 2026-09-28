<?php

namespace App\Http\Requests\Sales;

use App\Services\MasterData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('sales.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_type' => ['required', Rule::in(MasterData::codes('customer_type'))],
        ];
    }
}
