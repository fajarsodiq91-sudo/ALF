<?php

namespace App\Models;

use Database\Factories\ServicePackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'features', 'starting_price', 'included_revisions', 'estimated_days', 'addons', 'is_active', 'sort_order'])]
class ServicePackage extends Model
{
    /** @use HasFactory<ServicePackageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starting_price' => 'decimal:2',
            'addons' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class);
    }

    /** @return list<string> */
    public function featureList(): array
    {
        return collect(preg_split('/\R/', (string) $this->features))->map(fn ($line) => trim($line))->filter()->values()->all();
    }

    /**
     * Turns the "Label | price" lines staff type into the stored add-on list; lines without a valid price are dropped.
     *
     * @return list<array{label: string, price: float}>
     */
    public static function parseAddons(?string $text): array
    {
        return collect(preg_split('/\R/', (string) $text))
            ->map(fn ($line) => array_map('trim', explode('|', $line, 2)))
            ->filter(fn ($parts) => count($parts) === 2 && $parts[0] !== '' && is_numeric(str_replace(['.', ','], '', $parts[1])))
            ->map(fn ($parts) => ['label' => mb_substr($parts[0], 0, 120), 'price' => (float) str_replace(['.', ','], '', $parts[1])])
            ->values()
            ->all();
    }

    /** The add-ons back as editable text, one "Label | price" per line. */
    public function addonsText(): string
    {
        return collect($this->addons ?? [])->map(fn ($addon) => $addon['label'].' | '.(int) $addon['price'])->implode("\n");
    }
}
