<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['service_order_id', 'kind', 'path', 'original_name', 'note'])]
class ServiceOrderFile extends Model
{
    /** From the customer: sample data and references. */
    public const BRIEF = 'brief';

    /** From the company, for review: always downloadable. */
    public const DRAFT = 'draft';

    /** From the company, the finished work: released once every invoice is paid. */
    public const FINAL = 'final';

    public const KINDS = [self::BRIEF => 'From customer', self::DRAFT => 'Draft', self::FINAL => 'Final'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class, 'service_order_id');
    }

    /** Whether the customer may download it now: their own and draft files always, final files only once fully paid. */
    public function releasedToCustomer(): bool
    {
        return $this->kind !== self::FINAL || $this->order->loadMissing('payments')->isFullyPaid();
    }
}
