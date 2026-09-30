<?php

namespace App\Http\Requests\Settings;

use App\Models\SiteItem;
use App\Services\MasterData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validates one card of the public site; which fields exist depends on the item's type (see SiteItem::TYPES). */
class SaveSiteItemRequest extends FormRequest
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
        $rules = [
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['sometimes', 'boolean'],
            'remove_image' => ['sometimes', 'boolean'],
        ];

        foreach (SiteItem::TYPES[$this->route('type')]['fields'] as $name => $field) {
            $rules[$name] = $this->rulesFor($name, $field);
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<int, mixed>
     */
    private function rulesFor(string $name, array $field): array
    {
        $presence = ($field['required'] ?? false) ? 'required' : 'nullable';

        return match ($name) {
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'icon' => [$presence, 'string', 'max:16'],
            'body' => [$presence, 'string', 'max:2000'],
            'number' => [$presence, 'integer', 'min:0', 'max:1000000'],
            'category' => [$presence, Rule::in(array_keys(MasterData::options('portfolio_category', $this->route('item')?->category)))],
            'link_url' => ($field['external'] ?? false)
                ? [$presence, 'url:http,https', 'max:500']
                : [$presence, 'string', 'max:500', 'starts_with:/,#,http://,https://,mailto:', 'not_regex:/^\/\//'],
            default => [$presence, 'string', 'max:'.($field['max'] ?? 255)],
        };
    }
}
