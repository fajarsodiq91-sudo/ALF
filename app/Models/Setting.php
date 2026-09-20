<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    private const CACHE_KEY = 'app.settings';

    /** Editable settings: key => label. */
    public const FIELDS = [
        'company_name' => 'Company name',
        'company_address' => 'Address',
        'company_phone' => 'Phone',
        'company_email' => 'Email',
        'company_npwp' => 'NPWP (tax ID)',
    ];

    public static function values(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = static::values()[$key] ?? null;

        return $value === null || $value === '' ? $default : $value;
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }
}
