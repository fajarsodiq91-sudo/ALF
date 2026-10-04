<?php

namespace App\Http\Requests\Hr;

use App\Models\Employee;
use App\Rules\UniqueRfid;
use App\Services\MasterData;
use App\Services\Rfid;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('hr.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['rfid_uid' => Rfid::normalize($this->input('rfid_uid'))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'position' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['required', Rule::in(MasterData::codes('employment_type'))],
            'status' => ['required', Rule::in(array_keys(Employee::STATUSES))],
            'join_date' => ['nullable', 'date'],
            'annual_leave_quota' => ['required', 'integer', 'min:0', 'max:365'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'rfid_uid' => ['nullable', 'string', 'max:64', new UniqueRfid],
            'signature' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
