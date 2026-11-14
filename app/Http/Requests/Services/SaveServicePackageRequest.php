<?php

namespace App\Http\Requests\Services;

use Illuminate\Foundation\Http\FormRequest;

class SaveServicePackageRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'features' => ['nullable', 'string', 'max:2000'],
            'starting_price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'included_revisions' => ['required', 'integer', 'min:0', 'max:20'],
            'estimated_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'addons_text' => ['nullable', 'string', 'max:3000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
