<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\TrainingMeetingAttendance;
use App\Models\TrainingSession;
use App\Models\TrainingSessionMeeting;
use Illuminate\Support\Collection;

/**
 * Marking a meeting done also records who was there. A one-person session fills itself; for a group, staff tick who
 * attended. Only the rows made here (method 'auto') are ever removed again, so taps and manual marks survive an undo.
 */
class MeetingAttendance
{
    /** Who is expected at the session's meetings: the group's participants, or the customer themself. */
    public static function attendees(TrainingSession $session): Collection
    {
        $people = $session->participants->isNotEmpty() ? $session->participants : collect([$session->customer]);

        return $people->filter()->unique('id')->values();
    }

    /** A group has to choose who attended; a single attendee is taken as present. */
    public static function needsChoice(TrainingSession $session): bool
    {
        return self::attendees($session)->count() > 1;
    }

    /**
     * Records the people ticked on the done form as present; a one-person session records its only attendee.
     * Anyone who already has a mark (tap or manual) is left as it is.
     *
     * @param  array<int, int|string>  $ticked  customer ids from the form
     */
    public static function recordOnDone(TrainingSessionMeeting $meeting, array $ticked): void
    {
        $session = $meeting->session->loadMissing(['participants', 'customer']);
        $attendees = self::attendees($session);
        $present = self::needsChoice($session)
            ? $attendees->whereIn('id', array_map('intval', $ticked))
            : $attendees;

        $existing = $meeting->attendances()->pluck('customer_id');

        $present->reject(fn (Customer $person) => $existing->contains($person->id))->each(
            fn (Customer $person) => TrainingMeetingAttendance::create([
                'training_session_meeting_id' => $meeting->id,
                'customer_id' => $person->id,
                'checked_in_at' => self::arrivalTime($meeting),
                'method' => 'auto',
            ])
        );
    }

    /** Takes back what marking done filled in; taps and manual marks stay. */
    public static function clearOnUndo(TrainingSessionMeeting $meeting): void
    {
        $meeting->attendances()->where('method', 'auto')->delete();
    }

    /** The meeting's start on the day it was held, but never later than the moment it was marked done. */
    private static function arrivalTime(TrainingSessionMeeting $meeting)
    {
        $held = $meeting->completed_at;

        if (! $meeting->start_time) {
            return $held;
        }

        $start = $held->copy()->setTimeFromTimeString($meeting->start_time);

        return $start->lt($held) ? $start : $held;
    }
}
