<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\MeetingRescheduleRequest;
use App\Models\TrainingSessionMeeting;
use App\Services\BookedSlots;
use App\Services\OperatingHours;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Where a customer asks to move one of their own remaining upcoming meetings to a different slot. */
class PortalRescheduleRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $customer = $request->user('customer');

        $data = $request->validate([
            'training_session_meeting_id' => ['required', 'integer', Rule::in($customer->reschedulableMeetingIds())],
            'requested_date' => ['required', 'date'],
            'requested_start_time' => ['required', 'date_format:H:i'],
            'requested_end_time' => ['required', 'date_format:H:i', 'after:requested_start_time'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $meeting = TrainingSessionMeeting::with('session.program')->findOrFail($data['training_session_meeting_id']);

        if ($meeting->rescheduleRequests()->where('status', 'pending')->exists()) {
            return back()->with('error', 'A reschedule request for this meeting is already pending.');
        }

        if ($data['requested_date'] === $meeting->meeting_date->toDateString()
            && $data['requested_start_time'] === substr((string) $meeting->start_time, 0, 5)) {
            return back()->with('error', 'Choose a different date or time than the current schedule.');
        }

        $violation = OperatingHours::violation(
            $data['requested_date'],
            $data['requested_start_time'],
            $data['requested_end_time'],
            $meeting->session->program?->session_minutes,
            $meeting->session->isCorporate(),
        );

        if ($violation) {
            return back()->with('error', $violation);
        }

        if (BookedSlots::conflicts($data['requested_date'], $data['requested_start_time'], $data['requested_end_time'])) {
            return back()->with('error', BookedSlots::describe($data['requested_date'], $data['requested_start_time'], $data['requested_end_time']).' is already booked. Choose a green slot on the calendar.');
        }

        $meeting->rescheduleRequests()->create([
            'customer_id' => $customer->id,
            'requested_date' => $data['requested_date'],
            'requested_start_time' => $data['requested_start_time'],
            'requested_end_time' => $data['requested_end_time'],
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('portal.dashboard')->with('status', 'Reschedule request submitted. We will review it and get back to you.');
    }

    public function destroy(Request $request, MeetingRescheduleRequest $rescheduleRequest): RedirectResponse
    {
        abort_unless($rescheduleRequest->customer_id === $request->user('customer')->id, 403);

        if (! $rescheduleRequest->isPending()) {
            return back()->with('error', 'Only pending requests can be cancelled.');
        }

        $rescheduleRequest->delete();

        return redirect()->route('portal.dashboard')->with('status', 'Reschedule request cancelled.');
    }
}
