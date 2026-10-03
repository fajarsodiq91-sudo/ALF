<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Mail\MeetingRescheduleApproved;
use App\Mail\MeetingRescheduleRejected;
use App\Models\Customer;
use App\Models\MeetingRescheduleRequest;
use App\Models\TrainingSession;
use App\Models\TrainingSessionMeeting;
use App\Services\BookedSlots;
use App\Services\CustomerNotifier;
use App\Services\OperatingHours;
use App\Services\PaymentInvoices;
use App\Services\SessionPaymentPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TrainingSessionMeetingController extends Controller
{
    public function store(Request $request, TrainingSession $session): RedirectResponse
    {
        $this->authorize('training.manage');

        $data = $request->validate([
            'meeting_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:255'],
            'topic' => ['nullable', 'string', 'max:255'],
        ]);

        if ($violation = OperatingHours::violation($data['meeting_date'], $data['start_time'] ?? null, $data['end_time'] ?? null, $session->program?->session_minutes, $session->isCorporate())) {
            return back()->withInput()->withErrors(['meeting_date' => $violation]);
        }

        if (BookedSlots::conflicts($data['meeting_date'], $data['start_time'] ?? null, $data['end_time'] ?? null)) {
            return back()->withInput()->withErrors([
                'meeting_date' => BookedSlots::describe($data['meeting_date'], $data['start_time'], $data['end_time']).' is already booked. Choose a green slot on the calendar.',
            ]);
        }

        $meeting = $session->meetings()->create($data);
        SessionPaymentPlan::syncDueMeeting($session);
        CustomerNotifier::meetingAdded($meeting);

        return redirect()->route('training.edit', $session)->with('status', 'Meeting added.');
    }

    public function removeParticipant(TrainingSession $session, Customer $participant): RedirectResponse
    {
        $this->authorize('training.manage');

        $session->participants()->detach($participant->id);
        $session->update(['participants_count' => $session->participants()->count()]);
        CustomerNotifier::participantRemoved($session, $participant);

        return redirect()->route('training.edit', $session)->with('status', 'Participant removed.');
    }

    /** Marks a meeting as done (realised) or back to upcoming. */
    public function toggle(TrainingSessionMeeting $meeting): RedirectResponse
    {
        $this->authorize('training.manage');

        $done = ! $meeting->is_completed;
        $meeting->update(['is_completed' => $done, 'completed_at' => $done ? now() : null]);
        PaymentInvoices::sendDue($meeting->session);
        CustomerNotifier::meetingToggled($meeting);

        return redirect()->route('training.edit', $meeting->training_session_id)
            ->with('status', $done ? 'Meeting marked as done.' : 'Meeting marked as upcoming again.');
    }

    public function destroy(TrainingSessionMeeting $meeting): RedirectResponse
    {
        $this->authorize('training.manage');

        $meeting->delete();
        SessionPaymentPlan::syncDueMeeting($meeting->session);
        CustomerNotifier::meetingRemoved($meeting);

        return redirect()->route('training.edit', $meeting->training_session_id)->with('status', 'Meeting deleted.');
    }

    /** Moves a meeting directly (no customer approval); the customer is told by email. Pending customer requests for it are closed. */
    public function reschedule(Request $request, TrainingSessionMeeting $meeting): RedirectResponse
    {
        $this->authorize('training.manage');

        $data = $request->validate([
            'meeting_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
        ]);

        $session = $meeting->session;
        $start = $data['start_time'] ?? null;
        $end = $data['end_time'] ?? null;

        if ($violation = OperatingHours::violation($data['meeting_date'], $start, $end, $session->program?->session_minutes, $session->isCorporate())) {
            return back()->with('error', "Cannot reschedule: {$violation}");
        }

        if ($start && $end && BookedSlots::conflicts($data['meeting_date'], $start, $end, null, $meeting->id)) {
            return back()->with('error', 'Cannot reschedule: '.BookedSlots::describe($data['meeting_date'], $start, $end).' is already booked.');
        }

        $originalLabel = $meeting->meeting_date->format('D, d M Y').' · '.($meeting->timeRange() ?? 'no time set');

        $meeting->update(['meeting_date' => $data['meeting_date'], 'start_time' => $start, 'end_time' => $end]);
        $meeting->rescheduleRequests()->where('status', 'pending')
            ->update(['status' => 'rejected', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);

        $message = 'Meeting rescheduled.';
        $message .= CustomerNotifier::meetingRescheduled($meeting->refresh(), $originalLabel)
            ? ' The customer has been notified by email.'
            : ' The email to the customer could NOT be sent, so please contact them manually.';

        return redirect()->route('training.edit', $meeting->training_session_id)->with('status', $message);
    }

    /** Approves a customer's reschedule request: moves the meeting and notifies them by email. */
    public function approveReschedule(MeetingRescheduleRequest $rescheduleRequest): RedirectResponse
    {
        $this->authorize('training.manage');

        if (! $rescheduleRequest->isPending()) {
            return back()->with('error', 'This request was already reviewed.');
        }

        $meeting = $rescheduleRequest->meeting;
        $originalLabel = $meeting->meeting_date->format('D, d M Y').' · '.($meeting->timeRange() ?? 'no time set');

        $date = $rescheduleRequest->requested_date->toDateString();
        $start = substr($rescheduleRequest->requested_start_time, 0, 5);
        $end = substr($rescheduleRequest->requested_end_time, 0, 5);

        if ($violation = OperatingHours::violation($date, $start, $end, $meeting->session->program?->session_minutes, $meeting->session->isCorporate())) {
            return back()->with('error', "Cannot approve: {$violation}");
        }

        if (BookedSlots::conflicts($date, $start, $end)) {
            return back()->with('error', 'Cannot approve: '.BookedSlots::describe($date, $start, $end).' has since been booked elsewhere.');
        }

        $meeting->update(['meeting_date' => $date, 'start_time' => $start, 'end_time' => $end]);
        $rescheduleRequest->update(['status' => 'approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);

        $message = 'Reschedule approved.';

        try {
            Mail::to($rescheduleRequest->customer->email)->send(new MeetingRescheduleApproved($rescheduleRequest, $originalLabel));
            $message .= ' The customer has been notified by email.';
        } catch (Throwable $exception) {
            Log::error('Could not send the reschedule-approved email.', ['request_id' => $rescheduleRequest->id, 'error' => $exception->getMessage()]);
            $message .= ' The email to the customer could NOT be sent, so please contact them manually.';
        }

        return redirect()->route('training.edit', $meeting->training_session_id)->with('status', $message);
    }

    /** Rejects a customer's reschedule request: the meeting keeps its current schedule. */
    public function rejectReschedule(MeetingRescheduleRequest $rescheduleRequest): RedirectResponse
    {
        $this->authorize('training.manage');

        if (! $rescheduleRequest->isPending()) {
            return back()->with('error', 'This request was already reviewed.');
        }

        $rescheduleRequest->update(['status' => 'rejected', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);

        $message = 'Reschedule rejected.';

        try {
            Mail::to($rescheduleRequest->customer->email)->send(new MeetingRescheduleRejected($rescheduleRequest));
            $message .= ' The customer has been notified by email.';
        } catch (Throwable $exception) {
            Log::error('Could not send the reschedule-rejected email.', ['request_id' => $rescheduleRequest->id, 'error' => $exception->getMessage()]);
            $message .= ' The email to the customer could NOT be sent, so please contact them manually.';
        }

        return redirect()->route('training.edit', $rescheduleRequest->meeting->training_session_id)->with('status', $message);
    }
}
