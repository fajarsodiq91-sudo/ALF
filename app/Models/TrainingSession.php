<?php

namespace App\Models;

use Database\Factories\TrainingSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'training_program_id', 'customer_id', 'instructor_id', 'start_date', 'end_date',
    'delivery_mode', 'location', 'participants_count', 'participant_limit', 'fee', 'payment_plan', 'status', 'notes',
    'materials_url', 'certificate_url',
])]
class TrainingSession extends Model
{
    /** @use HasFactory<TrainingSessionFactory> */
    use HasFactory;

    public const STATUSES = [
        'planned' => 'Planned',
        'ongoing' => 'Ongoing',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'fee' => 'decimal:2',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'instructor_id');
    }

    /** People from the customer's company who joined through the participant link and log in to the portal. */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'training_session_participants')->withTimestamps()->orderBy('customers.name');
    }

    /** Whether the participant link is switched on for this session (a limit was set). */
    public function acceptsParticipants(): bool
    {
        return $this->participant_limit !== null && $this->participant_token !== null;
    }

    /** Open while there is room and the session is neither finished nor cancelled. */
    public function participantLinkOpen(): bool
    {
        return $this->acceptsParticipants()
            && in_array($this->status, ['planned', 'ongoing'], true)
            && $this->participants()->count() < $this->participant_limit;
    }

    /** Creates the participant link token the first time a limit is set (and drops it when the limit is cleared). */
    public function syncParticipantToken(): void
    {
        if ($this->participant_limit !== null && $this->participant_token === null) {
            $this->forceFill(['participant_token' => Str::random(40)])->save();
        } elseif ($this->participant_limit === null && $this->participant_token !== null) {
            $this->forceFill(['participant_token' => null])->save();
        }
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TrainingSessionPayment::class)->orderBy('id');
    }

    public function paidAmount(): float
    {
        return (float) $this->payments->filter->isPaid()->sum('amount');
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(TrainingSessionMeeting::class)->orderBy('meeting_date')->orderBy('start_time');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }
}
