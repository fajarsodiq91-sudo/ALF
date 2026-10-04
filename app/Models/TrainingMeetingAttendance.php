<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['training_session_meeting_id', 'customer_id', 'checked_in_at', 'method', 'tap_device_id'])]
class TrainingMeetingAttendance extends Model
{
    protected function casts(): array
    {
        return ['checked_in_at' => 'datetime'];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(TrainingSessionMeeting::class, 'training_session_meeting_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
