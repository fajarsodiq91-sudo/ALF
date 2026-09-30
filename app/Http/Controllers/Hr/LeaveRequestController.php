<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\SubmitLeaveRequest;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\StaffNotifier;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function index(Request $request): View
    {
        $year = now()->year;

        $leaves = LeaveRequest::query()
            ->with('employee')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest('start_date')
            ->paginate(20)
            ->withQueryString();

        $employees = Employee::where('status', '!=', 'resigned')->orderBy('name')->get();
        $used = LeaveRequest::where('leave_type', LeaveRequest::ANNUAL)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->selectRaw('employee_id, sum(days) as total')
            ->groupBy('employee_id')
            ->pluck('total', 'employee_id');

        return view('erp.hr.leaves.index', [
            'leaves' => $leaves,
            'employees' => $employees,
            'used' => $used,
            'year' => $year,
        ]);
    }

    public function create(): View
    {
        $this->authorize('hr.manage');

        return view('erp.hr.leaves.create', [
            'employees' => Employee::where('status', '!=', 'resigned')->orderBy('name')->get(),
        ]);
    }

    public function store(SubmitLeaveRequest $request): RedirectResponse
    {
        $data = $request->validated();

        LeaveRequest::create([
            ...$data,
            'days' => LeaveRequest::countWorkingDays(Carbon::parse($data['start_date']), Carbon::parse($data['end_date'])),
            'status' => 'pending',
        ]);

        return redirect()->route('hr.leaves.index')->with('status', 'Leave request submitted.');
    }

    public function approve(LeaveRequest $leave): RedirectResponse
    {
        $this->authorize('hr.manage');

        if (! $leave->isPending()) {
            return redirect()->route('hr.leaves.index')->with('error', 'Only pending requests can be approved.');
        }

        if ($leave->leave_type === LeaveRequest::ANNUAL) {
            $remaining = $leave->employee->annualLeaveRemaining($leave->start_date->year);

            if ($leave->days > $remaining) {
                return redirect()->route('hr.leaves.index')
                    ->with('error', "Cannot approve: only {$remaining} annual leave day(s) left for {$leave->employee->name}.");
            }
        }

        return $this->reviewed('Leave request approved.', $this->review($leave, 'approved'));
    }

    public function reject(LeaveRequest $leave): RedirectResponse
    {
        $this->authorize('hr.manage');

        if (! $leave->isPending()) {
            return redirect()->route('hr.leaves.index')->with('error', 'Only pending requests can be rejected.');
        }

        return $this->reviewed('Leave request rejected.', $this->review($leave, 'rejected'));
    }

    public function destroy(LeaveRequest $leave): RedirectResponse
    {
        $this->authorize('hr.manage');

        $leave->delete();

        return redirect()->route('hr.leaves.index')->with('status', 'Leave request deleted.');
    }

    /** Records the decision and emails the employee; returns whether that email went out. */
    private function review(LeaveRequest $leave, string $status): bool
    {
        $leave->update([
            'status' => $status,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return StaffNotifier::leaveReviewed($leave);
    }

    private function reviewed(string $message, bool $notified): RedirectResponse
    {
        $back = redirect()->route('hr.leaves.index');

        return $notified
            ? $back->with('status', "{$message} The employee was notified by email.")
            : $back->with('error', "{$message} The employee could NOT be notified by email (no email address on file, or the mail settings need checking).");
    }
}
