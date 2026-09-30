<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['training_program_id', 'path', 'sort_order'])]
class TrainingProgramImage extends Model
{
    protected static function booted(): void
    {
        static::deleted(function (TrainingProgramImage $image) {
            Storage::disk('public')->delete($image->path);
        });
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function url(): string
    {
        return asset('storage/'.$this->path);
    }
}
