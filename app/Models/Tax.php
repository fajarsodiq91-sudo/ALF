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

    public const TYPES = [
        self::TYPE_VAT => 'PPN / VAT (added to amount)',
        self::TYPE_WITHHOLDING => 'PPh / Withholding (deducted from amount)',
    ];

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
