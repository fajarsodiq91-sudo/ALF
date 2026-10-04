<?php

namespace App\Models;

use App\Models\Concerns\HasIdCardToken;
use App\Services\EmployeeNumberGenerator;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'employee_number', 'name', 'email', 'phone', 'position', 'department',
    'employment_type', 'status', 'join_date', 'annual_leave_quota', 'address', 'notes', 'signature_path', 'photo_path', 'rfid_uid',
])]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    use HasIdCardToken;

    public const STATUSES = [
        'active' => 'Active',
        'on_leave' => 'On Leave',
        'resigned' => 'Resigned',
    ];

    /** The employee number is assigned once on creation (YYMM + running number) and never edited. */
    protected static function booted(): void
    {
        static::creating(function (Employee $employee) {
            $employee->employee_number ??= EmployeeNumberGenerator::next(now());
        });

        static::deleted(function (Employee $employee) {
            foreach ([$employee->signature_path, $employee->photo_path] as $path) {
                if ($path) {
                    Storage::disk('public')->delete($path);
                }
            }
        });
    }

    /** The list filters (status, employment type, search) shared by the employee list and its bulk ID-card print. */
    public function scopeFiltered(Builder $query, Request $request): void
    {
        $query
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($query) => $query->where('employment_type', $request->string('type')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(fn ($inner) => $inner->where('name', 'like', $term)
                    ->orWhere('employee_number', 'like', $term)
                    ->orWhere('position', 'like', $term)
                    ->orWhere('department', 'like', $term));
            });
    }

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

    /** The scanned signature shown on certificates when this employee is the instructor. */
    public function signatureUrl(): ?string
    {
        return $this->signature_path ? asset('storage/'.$this->signature_path) : null;
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? asset('storage/'.$this->photo_path) : null;
    }

    /** Up to two initials, shown when there is no photo. */
    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
    }
}
