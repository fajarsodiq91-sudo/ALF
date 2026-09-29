<?php

namespace App\Models;

use Database\Factories\MeetingRescheduleRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A customer's request to move one of their remaining upcoming meetings to a different date/time. */
#[Fillable([
    'training_session_meeting_id', 'customer_id', 'requested_date',
    'requested_start_time', 'requested_end_time', 'reason',
    'status', 'reviewed_by', 'reviewed_at',
])]
class MeetingRescheduleRequest extends Model
{
    /** @use HasFactory<MeetingRescheduleRequestFactory> */
    use HasFactory;

    public const STATUSES = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    protected function casts(): array
    {
        return [
            'requested_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(TrainingSessionMeeting::class, 'training_session_meeting_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /** "Tue, 06 Oct 2026 · 20:00 – 21:30" for the requested slot. */
    public function requestedLabel(): string
    {
        return $this->requested_date->format('D, d M Y').' · '.substr($this->requested_start_time, 0, 5).' – '.substr($this->requested_end_time, 0, 5);
    }
}
