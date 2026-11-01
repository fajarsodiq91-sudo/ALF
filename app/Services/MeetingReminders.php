<?php

namespace App\Services;

use App\Models\TrainingSessionMeeting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Reminds customers of their meetings, the day before and an hour before they start, and gives the staff one daily summary.
 * Each reminder is stamped on the meeting so it goes out once; moving the meeting clears the stamps.
 * A reminder is stamped even when the email fails (that is logged), so a broken mailbox is not retried every minute.
 */
class MeetingReminders
{
    /** Emails the customers whose meeting is tomorrow; returns how many meetings were reminded. */
    public static function sendDayBefore(): int
    {
        $meetings = self::upcoming()->whereDate('meeting_date', today()->addDay())->whereNull('day_before_reminded_at')->get();

        foreach ($meetings as $meeting) {
            CustomerNotifier::meetingReminder($meeting, hourBefore: false);
            $meeting->forceFill(['day_before_reminded_at' => now()])->save();
        }

        return $meetings->count();
    }

    /** Emails the customers whose meeting starts within the next hour; returns how many meetings were reminded. */
    public static function sendHourBefore(): int
    {
        $meetings = self::upcoming()
            ->whereDate('meeting_date', '>=', today())
            ->whereDate('meeting_date', '<=', today()->addDay())
            ->whereNotNull('start_time')
            ->whereNull('hour_before_reminded_at')
            ->get()
            ->filter(fn (TrainingSessionMeeting $meeting) => $meeting->startsAt()->gt(now()) && $meeting->startsAt()->lte(now()->addHour()));

        foreach ($meetings as $meeting) {
            CustomerNotifier::meetingReminder($meeting, hourBefore: true);
            $meeting->forceFill(['hour_before_reminded_at' => now()])->save();
        }

        return $meetings->count();
    }

    /** Emails the staff one summary of today's and tomorrow's meetings; returns how many meetings it lists. */
    public static function sendStaffSummary(): int
    {
        $today = self::onDate(today());
        $tomorrow = self::onDate(today()->addDay());

        if ($today->isNotEmpty() || $tomorrow->isNotEmpty()) {
            StaffNotifier::meetingSummary($today, $tomorrow);
        }

        return $today->count() + $tomorrow->count();
    }

    /** @return Collection<int, TrainingSessionMeeting> */
    private static function onDate($date): Collection
    {
        return self::upcoming()->whereDate('meeting_date', $date)->get()->sortBy(fn ($meeting) => $meeting->start_time ?? '99')->values();
    }

    /** @return Builder<TrainingSessionMeeting> meetings still to happen, of sessions that are not cancelled */
    private static function upcoming(): Builder
    {
        return TrainingSessionMeeting::query()
            ->with('session.program', 'session.customer')
            ->where('is_completed', false)
            ->whereHas('session', fn ($query) => $query->where('status', '!=', 'cancelled'));
    }
}
