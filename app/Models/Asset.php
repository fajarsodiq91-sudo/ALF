<?php

namespace App\Models;

use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'asset_code', 'name', 'category', 'status', 'purchase_date',
    'purchase_cost', 'location', 'assigned_to', 'notes',
])]
class Asset extends Model
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory;

    public const STATUSES = [
        'active' => 'Active',
        'in_repair' => 'In Repair',
        'disposed' => 'Disposed',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'purchase_cost' => 'decimal:2',
        ];
    }
}
