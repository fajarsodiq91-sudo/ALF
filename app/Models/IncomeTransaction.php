<?php

namespace App\Models;

use App\Models\Concerns\HasProof;
use Database\Factories\IncomeTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'transaction_number', 'transaction_date', 'account_id', 'category_id',
    'source', 'description', 'amount', 'payment_method',
    'notes', 'created_by',
    'subtotal', 'tax_id', 'tax_rate', 'tax_amount',
    'proof_path', 'proof_original_name', 'proof_url',
])]
class IncomeTransaction extends Model
{
    /** @use HasFactory<IncomeTransactionFactory> */
    use HasFactory, HasProof;

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
