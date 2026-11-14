<?php

namespace App\Http\Requests\Services;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class QuoteServiceOrderRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.label' => ['required', 'string', 'max:255'],
            'items.*.amount' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'dp_percentage' => ['required', 'integer', 'min:10', 'max:100'],
            'estimated_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'included_revisions' => ['required', 'integer', 'min:0', 'max:20'],
            'quote_valid_until' => ['nullable', 'date', 'after_or_equal:today'],
            'quote_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isEmpty() && collect($this->input('items'))->sum('amount') <= 0) {
                $validator->errors()->add('items', 'The quote total must be more than zero.');
            }
        }];
    }
}
