<?php

namespace App\Models;

use Database\Factories\TrainingSessionMeetingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
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
