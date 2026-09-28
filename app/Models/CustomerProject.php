<?php

namespace App\Models;

use Database\Factories\CustomerProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['customer_id', 'training_session_id', 'title', 'description', 'file_path', 'file_name', 'external_url', 'in_portfolio'])]
class CustomerProject extends Model
{
    /** @use HasFactory<CustomerProjectFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleted(function (CustomerProject $project) {
            if ($project->file_path) {
                Storage::disk('local')->delete($project->file_path);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'in_portfolio' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }
}
