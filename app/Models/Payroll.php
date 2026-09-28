<?php

namespace App\Models;

use Database\Factories\PayrollFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id', 'period', 'basic_salary', 'allowances', 'deductions',
    'net_salary', 'status', 'paid_date', 'expense_transaction_id', 'notes',
])]
class Payroll extends Model
{
    /** @use HasFactory<PayrollFactory> */
    use HasFactory;

    public const DRAFT = 'draft';

    public const PAID = 'paid';

    public const SALARY_CATEGORY = 'Salaries & Wages';

    public const PAYMENT_METHODS = ['Bank Transfer', 'Cash'];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'allowances' => 'decimal:2',
            'deductions' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'paid_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function expenseTransaction(): BelongsTo
    {
        return $this->belongsTo(ExpenseTransaction::class);
    }

    public function isPaid(): bool
    {
        return $this->status === self::PAID;
    }

    public static function netFor(float|string $basic, float|string $allowances, float|string $deductions): float
    {
        return round((float) $basic + (float) $allowances - (float) $deductions, 2);
    }
}
