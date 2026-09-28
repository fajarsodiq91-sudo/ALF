<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'employee_number', 'name', 'email', 'phone', 'position', 'department',
    'employment_type', 'status', 'join_date', 'annual_leave_quota', 'address', 'notes',
])]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    public const STATUSES = [
        'active' => 'Active',
        'on_leave' => 'On Leave',
        'resigned' => 'Resigned',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
        ];
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    /** Approved annual-leave working days that start in the given year. */
    public function annualLeaveUsed(int $year): int
    {
        return (int) $this->leaveRequests()
            ->where('leave_type', LeaveRequest::ANNUAL)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->sum('days');
    }

    public function annualLeaveRemaining(int $year): int
    {
        return $this->annual_leave_quota - $this->annualLeaveUsed($year);
    }
}
