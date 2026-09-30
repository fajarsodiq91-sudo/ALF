<?php

namespace App\Http\Requests\Settings;

use App\Services\SiteContent;
use Illuminate\Foundation\Http\FormRequest;

/** Validates one website page's editable fields, built from the definitions in SiteContent. */
class SaveSitePageRequest extends FormRequest
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
        $rules = [];

        foreach (SiteContent::fields($this->route('page')) as $name => $field) {
            if ($field['type'] === 'image') {
                $rules["images.$name"] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'];

                continue;
            }

            $rules["content.$name"] = match ($field['type']) {
                'email' => ['nullable', 'email', 'max:255'],
                'url' => ['nullable', 'url:http,https', 'max:500'],
                'textarea' => ['nullable', 'string', 'max:'.($field['max'] ?? 2000)],
                default => ['nullable', 'string', 'max:'.($field['max'] ?? 255)],
            };
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [];

        foreach (SiteContent::fields($this->route('page')) as $name => $field) {
            $attributes[($field['type'] === 'image' ? 'images.' : 'content.').$name] = strtolower($field['label']);
        }

        return $attributes;
    }
}
