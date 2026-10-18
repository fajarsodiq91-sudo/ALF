<?php

namespace App\Services;

use App\Models\TrainingSession;

/**
 * A session with meetings follows them: planned until one is done, ongoing while some are done, completed once all
 * are. Cancelling stays a manual decision, and a session without meetings keeps the status staff set by hand.
 */
class SessionStatus
{
    /** The status the meetings call for; null when the session has none, so there is nothing to follow. */
    public static function fromMeetings(TrainingSession $session): ?string
    {
        $total = $session->meetings()->count();

        if ($total === 0) {
            return null;
        }

        $done = $session->meetings()->where('is_completed', true)->count();

        return match (true) {
            $done === $total => 'completed',
            $done > 0 => 'ongoing',
            default => 'planned',
        };
    }

    /** Brings the status in line with the meetings; completing a session also issues its certificates. */
    public static function sync(TrainingSession $session): void
    {
        $status = $session->status === 'cancelled' ? null : self::fromMeetings($session);

        if ($status === null || $status === $session->status) {
            return;
        }

        $session->update(['status' => $status]);

        if ($status === 'completed') {
            CustomerNotifier::sessionStatusChanged($session);
            CertificateIssuer::issueFor($session);
        }
    }

    /**
     * The status to store when staff save the session form. Meetings decide it, so the form can only cancel a
     * session or reopen a cancelled one ('auto'); a plain save leaves the current status untouched.
     */
    public static function forSave(TrainingSession $session, string $posted): string
    {
        if ($session->meetings()->doesntExist()) {
            return $posted === 'auto' ? 'planned' : $posted;
        }

        return match ($posted) {
            'cancelled' => 'cancelled',
            'auto' => self::fromMeetings($session),
            default => $session->status,
        };
    }
}
