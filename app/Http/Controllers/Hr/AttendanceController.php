<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\SaveAttendanceRequest;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->input('month')) ? $request->input('month') : now()->format('Y-m');
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        $records = AttendanceRecord::query()
            ->with('employee')
            ->whereBetween('attendance_date', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()])
            ->when($request->filled('employee_id'), fn ($query) => $query->where('employee_id', $request->integer('employee_id')))
            ->orderByDesc('attendance_date')
            ->get();

        $recap = $records->groupBy('employee_id')->map(fn ($rows) => $rows->countBy('status'));

        return view('erp.hr.attendance.index', [
            'records' => $records,
            'recap' => $recap,
            'month' => $month,
            'employees' => Employee::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('hr.manage');

        return view('erp.hr.attendance.create', [
            'employees' => Employee::where('status', '!=', 'resigned')->orderBy('name')->get(),
        ]);
    }

    public function store(SaveAttendanceRequest $request): RedirectResponse
    {
        $record = AttendanceRecord::create($request->validated());

        return redirect()
            ->route('hr.attendance.index', ['month' => $record->attendance_date->format('Y-m')])
            ->with('status', 'Attendance recorded.');
    }

    public function edit(AttendanceRecord $attendance): View
    {
        $this->authorize('hr.manage');

        return view('erp.hr.attendance.edit', [
            'record' => $attendance,
            'employees' => Employee::orderBy('name')->get(),
        ]);
    }

    public function update(SaveAttendanceRequest $request, AttendanceRecord $attendance): RedirectResponse
    {
        $attendance->update($request->validated());

        return redirect()
            ->route('hr.attendance.index', ['month' => $attendance->attendance_date->format('Y-m')])
            ->with('status', 'Attendance updated.');
    }

    public function destroy(AttendanceRecord $attendance): RedirectResponse
    {
        $this->authorize('hr.manage');

        $month = $attendance->attendance_date->format('Y-m');
        $attendance->delete();

        return redirect()->route('hr.attendance.index', ['month' => $month])->with('status', 'Attendance deleted.');
    }
}
