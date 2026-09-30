<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'type', 'rate', 'is_active', 'description'])]
class Tax extends Model
{
    use HasFactory;

    public const TYPE_VAT = 'vat';

    public const TYPE_WITHHOLDING = 'withholding';

    public const TYPE_FINAL = 'final';

    public const TYPES = [
        self::TYPE_VAT => 'PPN / VAT (added to amount)',
        self::TYPE_WITHHOLDING => 'PPh / Withholding (deducted from amount)',
        self::TYPE_FINAL => 'PPh Final (paid by the company, accrued — income is recorded in full)',
    ];

    public const SHORT_LABELS = [
        self::TYPE_VAT => 'PPN / VAT',
        self::TYPE_WITHHOLDING => 'PPh / Withholding',
        self::TYPE_FINAL => 'PPh Final',
    ];

    public const DEFAULT_INCOME_SETTING = 'default_income_tax_id';

    /** The tax applied automatically to new income, chosen in System Settings. */
    public static function defaultForIncome(): ?self
    {
        $id = Setting::get(self::DEFAULT_INCOME_SETTING);

        return $id ? static::where('is_active', true)->find($id) : null;
    }

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function incomeTransactions(): HasMany
    {
        return $this->hasMany(IncomeTransaction::class);
    }

    public function expenseTransactions(): HasMany
    {
        return $this->hasMany(ExpenseTransaction::class);
    }
}
