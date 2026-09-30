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
    'discount_type', 'discount_value', 'discount_expires_at',
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

    /** How much is taken off the standard price while the promo is active — never more than the price itself. */
    public function discountAmount(): float
    {
        if (! $this->hasActiveDiscount()) {
            return 0.0;
        }

        $amount = $this->discount_type === self::DISCOUNT_PERCENTAGE
            ? (float) $this->standard_price * ((float) $this->discount_value / 100)
            : (float) $this->discount_value;

        return min($amount, (float) $this->standard_price);
    }

    /** What a customer actually pays: the standard price minus the active promo, if any. */
    public function finalPrice(): float
    {
        return (float) $this->standard_price - $this->discountAmount();
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
