<?php

namespace App\Services;

use App\Mail\LeaveRequestReviewed;
use App\Mail\StaffRegistrationSubmitted;
use App\Mail\StaffRescheduleRequested;
use App\Models\Customer;
use App\Models\LeaveRequest;
use App\Models\MeetingRescheduleRequest;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Emails the owner and staff about things waiting for them, and employees about decisions on their requests.
 * Staff are the active users whose role grants the permission needed to act on it.
 * A failed send is logged and never blocks the action that triggered it.
 */
class StaffNotifier
{
    /** A customer submitted their registration and waits for approval. */
    public static function registrationSubmitted(Customer $customer): void
    {
        self::sendToUsersWith('sales.manage', fn () => new StaffRegistrationSubmitted($customer), ['customer_id' => $customer->id]);
    }

    /** A customer asked to move one of their meetings. */
    public static function rescheduleRequested(MeetingRescheduleRequest $rescheduleRequest): void
    {
        self::sendToUsersWith('training.manage', fn () => new StaffRescheduleRequested($rescheduleRequest), ['request_id' => $rescheduleRequest->id]);
    }

    /** Tells the employee how their leave request was decided; returns whether the email went out. */
    public static function leaveReviewed(LeaveRequest $leave): bool
    {
        $leave->loadMissing('employee');

        if (! $leave->employee->email) {
            return false;
        }

        try {
            Mail::to($leave->employee->email)->send(new LeaveRequestReviewed($leave));

            return true;
        } catch (Throwable $exception) {
            Log::error('Could not send the leave decision email.', ['leave_id' => $leave->id, 'error' => $exception->getMessage()]);

            return false;
        }
    }

    /**
     * One email per recipient, each from a fresh mailable (a mailable accumulates the recipients it is sent to).
     *
     * @param  Closure(): Mailable  $mailable
     * @param  array<string, mixed>  $context
     */
    private static function sendToUsersWith(string $permission, Closure $mailable, array $context): void
    {
        try {
            $emails = User::permission($permission)->where('is_active', true)->whereNotNull('email')->pluck('email')->unique();
        } catch (Throwable $exception) {
            Log::error('Could not find who to notify.', [...$context, 'permission' => $permission, 'error' => $exception->getMessage()]);

            return;
        }

        if ($emails->isEmpty()) {
            Log::warning('Nobody to notify: no active user has this permission.', [...$context, 'permission' => $permission]);
        }

        foreach ($emails as $email) {
            try {
                Mail::to($email)->send($mailable());
            } catch (Throwable $exception) {
                Log::error('Could not send the staff notification.', [...$context, 'to' => $email, 'error' => $exception->getMessage()]);
            }
        }
    }
}
