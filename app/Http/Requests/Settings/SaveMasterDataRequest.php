<?php

namespace App\Http\Requests\Settings;

use App\Models\MasterDataItem;
use App\Services\MasterData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveMasterDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('masterdata.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'group' => [$this->route('masterDataItem') ? 'prohibited' : 'required', Rule::in(array_keys(MasterData::GROUPS))],
            'label' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $current = $this->route('masterDataItem');
            $group = $current?->group ?? $this->input('group');

            $duplicate = MasterDataItem::where('group', $group)
                ->whereRaw('lower(label) = ?', [mb_strtolower(trim($this->input('label')))])
                ->when($current, fn ($query) => $query->whereKeyNot($current->getKey()))
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('label', 'This option already exists in the group.');
            }
        }];
    }
}
