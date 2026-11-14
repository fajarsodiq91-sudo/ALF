<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['service_order_id', 'kind', 'label', 'amount', 'invoice_sent_at', 'paid_date', 'income_transaction_id', 'proof_path', 'proof_original_name', 'proof_url'])]
class ServiceOrderPayment extends Model
{
    public const DOWN_PAYMENT = 'dp';

    public const FINAL = 'final';

    public const EXTRA = 'extra';

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_date' => 'date',
            'invoice_sent_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class, 'service_order_id');
    }

    public function incomeTransaction(): BelongsTo
    {
        return $this->belongsTo(IncomeTransaction::class);
    }

    /** Paid means the money was recorded as income in Finance; deleting that income makes it unpaid again. */
    public function isPaid(): bool
    {
        return $this->income_transaction_id !== null;
    }

    public function hasProof(): bool
    {
        return $this->proof_path !== null || $this->proof_url !== null;
    }

    public function invoiceNumber(): string
    {
        return 'INV-SVC-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    /** The down payment and extra charges are due at once; the final payment once the work was delivered. */
    public function isDue(): bool
    {
        return $this->kind !== self::FINAL || $this->order->delivered_at !== null;
    }

    public function dueLabel(): string
    {
        return $this->kind === self::FINAL ? 'When the work is delivered' : 'Now';
    }
}
