<?php

namespace App\Models;

use Database\Factories\TrainingSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'training_program_id', 'customer_id', 'instructor_id', 'start_date', 'end_date',
    'delivery_mode', 'location', 'participants_count', 'fee', 'status', 'notes',
    'materials_url', 'certificate_url',
])]
class TrainingSession extends Model
{
    /** @use HasFactory<TrainingSessionFactory> */
    use HasFactory;

    public const STATUSES = [
        'planned' => 'Planned',
        'ongoing' => 'Ongoing',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'fee' => 'decimal:2',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'instructor_id');
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(TrainingSessionMeeting::class)->orderBy('meeting_date')->orderBy('start_time');
    }
}
