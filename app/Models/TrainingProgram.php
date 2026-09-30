<?php

namespace App\Models;

use Database\Factories\TrainingProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'program_type', 'is_corporate', 'training_category_id', 'description', 'terms', 'duration_days', 'session_minutes', 'standard_price', 'is_active',
    'discount_type', 'discount_value', 'discount_expires_at', 'group_max_size', 'group_tiers',
])]
class TrainingProgram extends Model
{
    /** @use HasFactory<TrainingProgramFactory> */
    use HasFactory;

    public const DISCOUNT_PERCENTAGE = 'percentage';

    public const DISCOUNT_FIXED = 'fixed';

    public const DISCOUNT_TYPES = [
        self::DISCOUNT_PERCENTAGE => 'Percentage (%)',
        self::DISCOUNT_FIXED => 'Fixed amount (Rp)',
    ];

    protected function casts(): array
    {
        return [
            'standard_price' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_expires_at' => 'date',
            'is_active' => 'boolean',
            'is_corporate' => 'boolean',
            'group_max_size' => 'integer',
            'group_tiers' => 'array',
        ];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TrainingCategory::class, 'training_category_id');
    }

    /** Illustration photos shown to customers while they choose a program, in display order. */
    public function images(): HasMany
    {
        return $this->hasMany(TrainingProgramImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Whether a promo is set up and its "active until" date has not passed yet. */
    public function hasActiveDiscount(): bool
    {
        return $this->discount_type !== null
            && (float) $this->discount_value > 0
            && ($this->discount_expires_at === null || ! $this->discount_expires_at->endOfDay()->isPast());
    }

    /** How much is taken off a per-person price while the promo is active — never more than the price itself. */
    public function discountAmount(?float $base = null): float
    {
        $base ??= (float) $this->standard_price;

        if (! $this->hasActiveDiscount()) {
            return 0.0;
        }

        $amount = $this->discount_type === self::DISCOUNT_PERCENTAGE
            ? $base * ((float) $this->discount_value / 100)
            : (float) $this->discount_value;

        return min($amount, $base);
    }

    /** What one person pays: the standard price minus the active promo, if any. */
    public function finalPrice(): float
    {
        return $this->pricePerPerson(1);
    }

    /** Whether individuals may sign up together (2+ people) at a reduced per-person price. */
    public function allowsGroups(): bool
    {
        return ($this->group_max_size ?? 1) > 1;
    }

    /** The most people that can register together; 1 when group pricing is off. */
    public function maxGroupSize(): int
    {
        return max(1, (int) $this->group_max_size);
    }

    /**
     * Group price tiers, lowest first: each tier applies from `min` people up to the next tier's `min`.
     *
     * @return list<array{min: int, price: float}>
     */
    public function groupTiers(): array
    {
        return collect($this->group_tiers ?? [])
            ->map(fn ($tier) => ['min' => (int) $tier['min'], 'price' => (float) $tier['price']])
            ->sortBy('min')
            ->values()
            ->all();
    }

    /** Per-person price before any promo for a group of this size: the highest tier reached, else the standard price. */
    public function basePricePerPerson(int $size): float
    {
        $size = min(max(1, $size), $this->maxGroupSize());
        $price = (float) $this->standard_price;

        foreach ($this->groupTiers() as $tier) {
            if ($size >= $tier['min']) {
                $price = $tier['price'];
            }
        }

        return $price;
    }

    /** What each person of a group pays: the tier price minus the active promo, if any. */
    public function pricePerPerson(int $size): float
    {
        $base = $this->basePricePerPerson($size);

        return $base - $this->discountAmount($base);
    }

    /** What the whole group pays together. */
    public function groupTotal(int $size): float
    {
        return $this->pricePerPerson($size) * min(max(1, $size), $this->maxGroupSize());
    }

    /**
     * Per-person price for every possible group size, for the pages that recalculate live.
     *
     * @return array<int, float>
     */
    public function pricesBySize(): array
    {
        return collect(range(1, $this->maxGroupSize()))->mapWithKeys(fn ($size) => [$size => $this->pricePerPerson($size)])->all();
    }

    /** Short label for the promo while it is active, e.g. "10% off" or "Rp 500.000 off". */
    public function discountLabel(): ?string
    {
        if (! $this->hasActiveDiscount()) {
            return null;
        }

        return $this->discount_type === self::DISCOUNT_PERCENTAGE
            ? rtrim(rtrim(number_format((float) $this->discount_value, 2), '0'), '.').'% off'
            : 'Rp '.number_format((float) $this->discount_value, 0, ',', '.').' off';
    }
}
