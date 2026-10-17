<?php

namespace App\Http\Requests\Sales;

use App\Rules\UniqueRfid;
use App\Services\MasterData;
use App\Services\Rfid;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('sales.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['rfid_uid' => Rfid::normalize($this->input('rfid_uid'))]);
    }

    public function messages(): array
    {
        return [
            'customer_code.regex' => 'The customer ID must be 6 digits: year (YY), month (01-12) and a two-digit number, e.g. 260801.',
            'customer_code.unique' => 'This customer ID is already used by another customer.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $customer = $this->route('customer');

        return [
            ...($customer->isManuallyAdded() ? ['customer_code' => ['sometimes', 'string', 'regex:/^\d{2}(0[1-9]|1[0-2])\d{2}$/', Rule::unique('customers', 'customer_code')->ignore($customer)]] : []),
            'name' => ['required', 'string', 'max:255'],
            'customer_type' => ['required', Rule::in(MasterData::codes('customer_type'))],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'rfid_uid' => ['nullable', 'string', 'max:64', new UniqueRfid($this->route('customer'))],
        ];
    }
}
