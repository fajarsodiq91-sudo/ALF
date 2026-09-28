<?php

namespace App\Models;

use Database\Factories\TrainingSessionPaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['training_session_id', 'label', 'percentage', 'amount', 'due_meeting_number', 'paid_date', 'income_transaction_id'])]
class TrainingSessionPayment extends Model
{
    /** @use HasFactory<TrainingSessionPaymentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'amount' => 'decimal:2',
            'paid_date' => 'date',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
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

    /** When it is due, in words: "Upon registration" or "At meeting 4". */
    public function dueLabel(): string
    {
        return $this->due_meeting_number ? 'At meeting '.$this->due_meeting_number : 'Upon registration';
    }

    /** Due now: upfront payments always, the others once their meeting was held or its date has come. */
    public function isDue(): bool
    {
        if ($this->due_meeting_number === null) {
            return true;
        }

        $meeting = $this->session->meetings->values()->get($this->due_meeting_number - 1);

        return $meeting !== null && ($meeting->is_completed || $meeting->meeting_date->lte(today()));
    }
}
