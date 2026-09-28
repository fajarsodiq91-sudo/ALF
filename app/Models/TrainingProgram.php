<?php

namespace App\Models;

use Database\Factories\TrainingProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'program_type', 'description', 'duration_days', 'standard_price', 'is_active'])]
class TrainingProgram extends Model
{
    /** @use HasFactory<TrainingProgramFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'standard_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }
}
