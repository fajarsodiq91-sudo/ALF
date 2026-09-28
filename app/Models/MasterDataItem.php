<?php

namespace App\Models;

use App\Services\MasterData;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['group', 'code', 'label', 'sort_order', 'is_active'])]
class MasterDataItem extends Model
{
    protected $table = 'master_data';

    protected static function booted(): void
    {
        $flush = fn () => app(MasterData::class)->flush();

        static::saved($flush);
        static::deleted($flush);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
