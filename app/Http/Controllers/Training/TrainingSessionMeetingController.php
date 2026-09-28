<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\TrainingSession;
use App\Models\TrainingSessionMeeting;
use App\Services\PaymentInvoices;
use App\Services\BookedSlots;
use App\Services\OperatingHours;
use App\Services\SessionPaymentPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        if ($violation = OperatingHours::violation($data['meeting_date'], $data['start_time'] ?? null, $data['end_time'] ?? null, $session->program?->session_minutes)) {
            return back()->withInput()->withErrors(['meeting_date' => $violation]);
        }

        if (BookedSlots::conflicts($data['meeting_date'], $data['start_time'] ?? null, $data['end_time'] ?? null)) {
            return back()->withInput()->withErrors([
                'meeting_date' => BookedSlots::describe($data['meeting_date'], $data['start_time'], $data['end_time']).' is already booked. Choose a green slot on the calendar.',
            ]);
        }

        $session->meetings()->create($data);
        SessionPaymentPlan::syncDueMeeting($session);

        return redirect()->route('training.edit', $session)->with('status', 'Meeting added.');
    }

    public function removeParticipant(TrainingSession $session, Customer $participant): RedirectResponse
    {
        $this->authorize('training.manage');

        $session->participants()->detach($participant->id);
        $session->update(['participants_count' => $session->participants()->count()]);

        return redirect()->route('training.edit', $session)->with('status', 'Participant removed.');
    }

    /** Marks a meeting as done (realised) or back to upcoming. */
    public function toggle(TrainingSessionMeeting $meeting): RedirectResponse
    {
        $this->authorize('training.manage');

        $done = ! $meeting->is_completed;
        $meeting->update(['is_completed' => $done, 'completed_at' => $done ? now() : null]);
        PaymentInvoices::sendDue($meeting->session);

        return redirect()->route('training.edit', $meeting->training_session_id)
            ->with('status', $done ? 'Meeting marked as done.' : 'Meeting marked as upcoming again.');
    }

    public function destroy(TrainingSessionMeeting $meeting): RedirectResponse
    {
        $this->authorize('training.manage');

        $meeting->delete();
        SessionPaymentPlan::syncDueMeeting($meeting->session);

        return redirect()->route('training.edit', $meeting->training_session_id)->with('status', 'Meeting deleted.');
    }
}
