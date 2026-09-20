<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'loan_number', 'direction', 'loan_date', 'account_id', 'party_name',
    'amount', 'description', 'notes', 'created_by',
])]
class Loan extends Model
{
    use HasFactory;

    /** Owner takes money out of the company; owner must pay it back. */
    public const OWNER_BORROWS = 'owner_borrows';

    /** Owner puts personal money into the company; company must pay it back. */
    public const COMPANY_BORROWS = 'company_borrows';

    public const DIRECTIONS = [
        self::OWNER_BORROWS => 'Owner borrows from company',
        self::COMPANY_BORROWS => 'Company borrows from owner',
    ];

    protected function casts(): array
    {
        return [
            'loan_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** +1 when the loan puts cash into the company account, -1 when it takes cash out. */
    public function disbursementSign(): int
    {
        return $this->direction === self::COMPANY_BORROWS ? 1 : -1;
    }

    public function repaidAmount(): float
    {
        return (float) $this->repayments->sum('amount');
    }

    public function outstandingAmount(): float
    {
        return round((float) $this->amount - $this->repaidAmount(), 2);
    }

    public function isSettled(): bool
    {
        return $this->outstandingAmount() <= 0;
    }
}
