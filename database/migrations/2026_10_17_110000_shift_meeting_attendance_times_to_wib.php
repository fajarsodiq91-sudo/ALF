<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Existing check-ins were stored while the app timezone was UTC, so they
     * are 7 hours behind local (WIB) time. Shift them once to Asia/Jakarta.
     */
    public function up(): void
    {
        $this->shift(7);
    }

    public function down(): void
    {
        $this->shift(-7);
    }

    private function shift(int $hours): void
    {
        $sign = $hours >= 0 ? '+' : '-';
        $abs = abs($hours);

        $expression = match (DB::connection()->getDriverName()) {
            'sqlite' => "datetime(checked_in_at, '{$sign}{$abs} hours')",
            'pgsql' => "checked_in_at {$sign} interval '{$abs} hours'",
            default => "checked_in_at {$sign} interval {$abs} hour",
        };

        DB::table('training_meeting_attendances')->update(['checked_in_at' => DB::raw($expression)]);
    }
};
