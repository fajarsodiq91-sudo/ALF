<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'account_type', 'account_number', 'opening_balance', 'is_active', 'description'])]
class Account extends Model
{
    /** @use HasFactory<\Database\Factories\AccountFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
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

    public function transfersOut(): HasMany
    {
        return $this->hasMany(Transfer::class, 'from_account_id');
    }

    public function transfersIn(): HasMany
    {
        return $this->hasMany(Transfer::class, 'to_account_id');
    }

    /**
     * Current balance = opening balance + income - expense + transfers in - transfers out.
     * Transfers never change the company's total balance across all accounts,
     * only which account the money sits in.
     */
    public function currentBalance(): float
    {
        $income = (float) $this->incomeTransactions()->sum('amount');
        $expense = (float) $this->expenseTransactions()->sum('amount');
        $transfersIn = (float) $this->transfersIn()->sum('amount');
        $transfersOut = (float) $this->transfersOut()->sum('amount');

        return (float) $this->opening_balance + $income - $expense + $transfersIn - $transfersOut;
    }
}
