<?php

namespace App\Models;

use Database\Factories\ServiceOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A piece of custom work a customer asks for (e.g. an Excel dashboard). It is priced by a quote, not by a catalog fee:
 * brief → quoted → accepted → in progress → delivered → completed.
 */
#[Fillable([
    'customer_id', 'service_package_id', 'title', 'purpose', 'kpis', 'data_sources', 'data_volume', 'excel_version',
    'needs_refresh', 'deadline', 'notes', 'addons',
])]
class ServiceOrder extends Model
{
    /** @use HasFactory<ServiceOrderFactory> */
    use HasFactory;

    public const BRIEF = 'brief';

    public const QUOTED = 'quoted';

    public const ACCEPTED = 'accepted';

    public const IN_PROGRESS = 'in_progress';

    public const DELIVERED = 'delivered';

    public const COMPLETED = 'completed';

    public const DECLINED = 'declined';

    public const CANCELLED = 'cancelled';

    public const STATUSES = [
        self::BRIEF => 'Brief received',
        self::QUOTED => 'Quote sent',
        self::ACCEPTED => 'Waiting for down payment',
        self::IN_PROGRESS => 'In progress',
        self::DELIVERED => 'Delivered for review',
        self::COMPLETED => 'Completed',
        self::DECLINED => 'Quote declined',
        self::CANCELLED => 'Cancelled',
    ];

    /** Badge colour per status, as Tailwind classes. */
    public const STATUS_STYLES = [
        self::BRIEF => 'bg-amber-50 text-amber-700',
        self::QUOTED => 'bg-blue-50 text-blue-700',
        self::ACCEPTED => 'bg-purple-50 text-purple-700',
        self::IN_PROGRESS => 'bg-brand-50 text-brand',
        self::DELIVERED => 'bg-teal-50 text-teal-700',
        self::COMPLETED => 'bg-green-50 text-green-700',
        self::DECLINED => 'bg-gray-100 text-gray-500',
        self::CANCELLED => 'bg-gray-100 text-gray-500',
    ];

    /** Maximum files per upload and their size, shared by every upload form. */
    public const FILE_MIMES = 'xlsx,xlsm,xls,csv,pdf,zip,docx,pptx,txt,png,jpg,jpeg,webp';

    public const FILE_MAX_KB = 20480;

    protected function casts(): array
    {
        return [
            'needs_refresh' => 'boolean',
            'deadline' => 'date',
            'addons' => 'array',
            'quote_items' => 'array',
            'quote_total' => 'decimal:2',
            'quote_valid_until' => 'date',
            'quoted_at' => 'datetime',
            'accepted_at' => 'datetime',
            'started_at' => 'datetime',
            'delivered_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class, 'service_package_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ServiceOrderPayment::class)->orderBy('id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ServiceOrderFile::class)->orderBy('id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ServiceOrderRevision::class)->orderBy('id');
    }

    public function orderNumber(): string
    {
        return 'SO-'.($this->created_at ?? now())->format('ym').'-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusStyle(): string
    {
        return self::STATUS_STYLES[$this->status] ?? 'bg-gray-100 text-gray-500';
    }

    public function hasStatus(string ...$statuses): bool
    {
        return in_array($this->status, $statuses, true);
    }

    /** Still open: neither finished nor ended. */
    public function isOpen(): bool
    {
        return ! $this->hasStatus(self::COMPLETED, self::DECLINED, self::CANCELLED);
    }

    /** Staff may (re)write the quote until the customer accepted it. */
    public function canBeQuoted(): bool
    {
        return $this->hasStatus(self::BRIEF, self::QUOTED);
    }

    public function quoteExpired(): bool
    {
        return $this->hasStatus(self::QUOTED) && $this->quote_valid_until !== null && $this->quote_valid_until->endOfDay()->isPast();
    }

    /** The agreed work has started once the down payment is in. */
    public function isWorkable(): bool
    {
        return $this->hasStatus(self::IN_PROGRESS, self::DELIVERED, self::COMPLETED);
    }

    public function totalCharged(): float
    {
        return (float) $this->payments->sum('amount');
    }

    public function totalPaid(): float
    {
        return (float) $this->payments->filter->isPaid()->sum('amount');
    }

    /** Every invoice raised so far is paid (and at least one exists). The final files are released only then. */
    public function isFullyPaid(): bool
    {
        return $this->payments->isNotEmpty() && $this->payments->every->isPaid();
    }

    public function downPaymentPaid(): bool
    {
        return $this->payments->where('kind', ServiceOrderPayment::DOWN_PAYMENT)->contains->isPaid();
    }

    public function revisionsUsed(): int
    {
        return $this->revisions->count();
    }

    public function revisionsLeft(): int
    {
        return max(0, $this->included_revisions - $this->revisionsUsed());
    }

    public function openRevisions(): int
    {
        return $this->revisions->whereNull('resolved_at')->count();
    }

    /** The customer may ask for changes while the work is delivered for review (a request puts it back in progress). */
    public function acceptsRevisionRequests(): bool
    {
        return $this->hasStatus(self::DELIVERED);
    }
}
