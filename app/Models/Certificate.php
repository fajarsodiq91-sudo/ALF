<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A completion certificate for one person in one finished learning session. */
class Certificate extends Model
{
    protected $fillable = ['training_session_id', 'customer_id', 'number', 'suffix', 'issued_at'];

    protected function casts(): array
    {
        return ['issued_at' => 'date'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
