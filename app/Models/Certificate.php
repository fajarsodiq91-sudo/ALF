<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** A completion certificate for one person in one finished learning session. */
class Certificate extends Model
{
    protected $fillable = ['training_session_id', 'customer_id', 'number', 'suffix', 'verification_code', 'issued_at', 'released_at'];

    /** Every certificate gets an unguessable code for its public verification page (the QR code target). */
    protected static function booted(): void
    {
        static::creating(function (self $certificate) {
            $certificate->verification_code ??= Str::random(24);
        });
    }

    public function verifyUrl(): string
    {
        return route('certificates.verify', $this->verification_code);
    }

    protected function casts(): array
    {
        return ['issued_at' => 'date', 'released_at' => 'datetime'];
    }

    /** Printed early by staff and not yet visible to the holder; released once the session is completed. */
    public function isReleased(): bool
    {
        return $this->released_at !== null;
    }

    /** Certificates the holder (and the public verification page) may see. */
    public function scopeReleased(Builder $query): void
    {
        $query->whereNotNull('released_at');
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
