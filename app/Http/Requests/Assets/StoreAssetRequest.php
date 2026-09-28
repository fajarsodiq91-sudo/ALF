<?php

namespace App\Http\Requests\Assets;

use App\Models\Asset;
use App\Services\MasterData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assets.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'asset_code' => ['required', 'string', 'max:50', Rule::unique('assets', 'asset_code')],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(MasterData::codes('asset_category'))],
            'status' => ['required', Rule::in(array_keys(Asset::STATUSES))],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['required', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
