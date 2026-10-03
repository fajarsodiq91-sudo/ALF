<?php

namespace App\Services;

use App\Mail\TrainingUpdateMail;
use App\Models\Certificate;
use App\Models\Customer;
use App\Models\TrainingSession;
use App\Models\TrainingSessionMeeting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Tells a customer about changes the team makes to their program. A failed send is logged and never blocks the action. */
class CustomerNotifier
{
    public static function sessionScheduled(TrainingSession $session): void
    {
        self::send($session->customer, 'Your training session is scheduled', 'Your training session has been scheduled.', self::sessionDetails($session));
    }

    public static function sessionStatusChanged(TrainingSession $session): void
    {
        $status = TrainingSession::STATUSES[$session->status] ?? ucfirst($session->status);

        self::send($session->customer, "Your training session is now {$status}", "The status of your training session is now {$status}.", self::sessionDetails($session));
    }

    public static function meetingAdded(TrainingSessionMeeting $meeting): void
    {
        self::send($meeting->session->customer, 'A meeting was added to your program', 'A new meeting has been added to your program.', self::meetingDetails($meeting));
    }

    /** @param  string  $originalLabel  the schedule before the team moved the meeting */
    public static function meetingRescheduled(TrainingSessionMeeting $meeting, string $originalLabel): bool
    {
        return self::send(
            $meeting->session->customer,
            'Your meeting schedule has changed',
            'We have changed the schedule of one of your meetings.',
            ['Previous schedule' => $originalLabel] + self::meetingDetails($meeting),
        );
    }

    public static function meetingRemoved(TrainingSessionMeeting $meeting): void
    {
        self::send($meeting->session->customer, 'A meeting was cancelled', 'The meeting below has been removed from your schedule.', self::meetingDetails($meeting));
    }

    public static function meetingToggled(TrainingSessionMeeting $meeting): void
    {
        $done = $meeting->is_completed;

        self::send(
            $meeting->session->customer,
            $done ? 'Your meeting is marked as done' : 'Your meeting is back on the schedule',
            $done ? 'The meeting below has been completed.' : 'The meeting below was marked as upcoming again.',
            self::meetingDetails($meeting),
        );
    }

    public static function participantRemoved(TrainingSession $session, Customer $participant): void
    {
        self::send($participant, 'You were removed from a training session', 'You are no longer a participant of the session below.', self::sessionDetails($session));
    }

    public static function certificateIssued(Certificate $certificate): void
    {
        $certificate->loadMissing(['customer', 'session.program']);

        self::send($certificate->customer, 'Your certificate is ready', 'Congratulations! Your certificate has been issued. You can download it from your portal.', [
            'Program' => $certificate->session->program?->name ?? '-',
            'Certificate number' => $certificate->number,
        ]);
    }

    /** @return array<string, string> */
    private static function sessionDetails(TrainingSession $session): array
    {
        $session->loadMissing('program');

        return array_filter([
            'Program' => $session->program?->name,
            'Period' => $session->start_date?->format('d M Y').($session->end_date && ! $session->end_date->isSameDay($session->start_date) ? ' – '.$session->end_date->format('d M Y') : ''),
            'Status' => TrainingSession::STATUSES[$session->status] ?? null,
        ]);
    }

    /** @return array<string, string> */
    private static function meetingDetails(TrainingSessionMeeting $meeting): array
    {
        $meeting->loadMissing('session.program');

        return array_filter([
            'Program' => $meeting->session->program?->name,
            'Date' => $meeting->meeting_date->format('D, d M Y'),
            'Time' => $meeting->timeRange(),
            'Location' => $meeting->location,
            'Topic' => $meeting->topic,
        ]);
    }

    /** @param  array<string, string>  $details */
    private static function send(?Customer $customer, string $heading, string $message, array $details): bool
    {
        if (! $customer?->email) {
            return false;
        }

        try {
            Mail::to($customer->email)->send(new TrainingUpdateMail($customer, $heading, $message, $details));

            return true;
        } catch (Throwable $exception) {
            Log::error('Could not send the customer update email.', ['customer_id' => $customer->id, 'heading' => $heading, 'error' => $exception->getMessage()]);

            return false;
        }
    }
}
