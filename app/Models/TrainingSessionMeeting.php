<?php

namespace App\Models;

use Database\Factories\TrainingSessionMeetingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['training_session_id', 'meeting_date', 'start_time', 'end_time', 'location', 'topic', 'is_completed', 'completed_at'])]
class TrainingSessionMeeting extends Model
{
    /** @use HasFactory<TrainingSessionMeetingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
            'day_before_reminded_at' => 'datetime',
            'hour_before_reminded_at' => 'datetime',
        ];
    }

    /** A meeting moved to another date or time is due its reminders again. */
    protected static function booted(): void
    {
        static::updating(function (self $meeting) {
            if ($meeting->isDirty(['meeting_date', 'start_time'])) {
                $meeting->day_before_reminded_at = null;
                $meeting->hour_before_reminded_at = null;
            }
        });
    }

    /** When the meeting begins, or null when no start time is set. */
    public function startsAt(): ?Carbon
    {
        return $this->start_time
            ? $this->meeting_date->copy()->setTimeFromTimeString($this->start_time)
            : null;
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(TrainingMeetingAttendance::class)->orderBy('checked_in_at');
    }

    public function rescheduleRequests(): HasMany
    {
        return $this->hasMany(MeetingRescheduleRequest::class, 'training_session_meeting_id')->latest();
    }

    /** "09:00 – 12:00", "09:00" or null, from the stored HH:MM:SS times. */
    public function timeRange(): ?string
    {
        if (! $this->start_time) {
            return null;
        }

        $start = substr($this->start_time, 0, 5);

        return $this->end_time ? $start.' – '.substr($this->end_time, 0, 5) : $start;
    }
}
