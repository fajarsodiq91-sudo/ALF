<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\TrainingMeetingAttendance;
use App\Models\TrainingSession;
use App\Models\TrainingSessionMeeting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Who attended which meeting of a session, with a manual fix-up for people who forgot their card. */
class TrainingAttendanceController extends Controller
{
    public function show(TrainingSession $session): View
    {
        $session->load(['program', 'customer', 'participants', 'meetings.attendances']);

        $meetings = $session->meetings->sortBy(fn ($meeting) => $meeting->meeting_date->toDateString().$meeting->start_time)->values();
        $attendances = $meetings->flatMap->attendances->groupBy('customer_id')->map(fn ($rows) => $rows->keyBy('training_session_meeting_id'));

        // Who is expected: the group's participants, or the customer themself for a one-person session. Anyone who tapped in is listed too.
        $people = ($session->participants->isNotEmpty() ? $session->participants : collect([$session->customer]))
            ->concat(Customer::whereIn('id', $attendances->keys())->get())
            ->unique('id')
            ->sortBy('name')
            ->values();

        // Meetings that have happened (or are marked done) are what attendance is measured against.
        $held = $meetings->filter(fn ($meeting) => $meeting->is_completed || $meeting->meeting_date->lte(today()));

        $rows = $people->map(function (Customer $person) use ($attendances, $held) {
            $mine = $attendances->get($person->id, collect());
            $present = $held->filter(fn ($meeting) => $mine->has($meeting->id))->count();

            return [
                'customer' => $person,
                'attendances' => $mine,
                'present' => $present,
                'percent' => $held->isEmpty() ? null : (int) round($present / $held->count() * 100),
            ];
        });

        return view('erp.training.sessions.attendance', [
            'session' => $session,
            'meetings' => $meetings,
            'heldCount' => $held->count(),
            'rows' => $rows,
        ]);
    }

    /** Marks a person present at a meeting by hand, or removes the mark. */
    public function toggle(TrainingSessionMeeting $meeting, Customer $customer): RedirectResponse
    {
        $this->authorize('training.manage');

        $existing = TrainingMeetingAttendance::where('training_session_meeting_id', $meeting->id)->where('customer_id', $customer->id)->first();

        if ($existing) {
            $existing->delete();
        } else {
            TrainingMeetingAttendance::create([
                'training_session_meeting_id' => $meeting->id,
                'customer_id' => $customer->id,
                'checked_in_at' => now(),
                'method' => 'manual',
            ]);
        }

        return back()->with('status', $existing ? 'Attendance removed.' : 'Marked present.');
    }
}
