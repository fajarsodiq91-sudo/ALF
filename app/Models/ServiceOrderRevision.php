<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['service_order_id', 'message', 'is_extra', 'resolved_at'])]
class ServiceOrderRevision extends Model
{
    protected function casts(): array
    {
        return [
            'is_extra' => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class, 'service_order_id');
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }
}
