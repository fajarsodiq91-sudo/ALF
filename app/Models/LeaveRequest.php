<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\LeaveRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id', 'leave_type', 'start_date', 'end_date', 'days', 'reason',
    'status', 'reviewed_by', 'reviewed_at',
])]
class LeaveRequest extends Model
{
    /** @use HasFactory<LeaveRequestFactory> */
    use HasFactory;

    public const ANNUAL = 'annual';

    public const STATUSES = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /** Counts Monday–Friday days between two dates, inclusive. */
    public static function countWorkingDays(CarbonInterface $start, CarbonInterface $end): int
    {
        $days = 0;

        for ($date = $start->copy()->startOfDay(); $date->lte($end); $date->addDay()) {
            if ($date->isWeekday()) {
                $days++;
            }
        }

        return $days;
    }
}
