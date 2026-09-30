<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const NAME = 'PPh Final UMKM (0,5%)';

    /**
     * PPh Final UMKM is the company's own tax, not something a customer withholds.
     * Income is now recorded in full and the tax accrues as a liability, so income
     * that was booked net of it is restored to the amount actually received.
     */
    public function up(): void
    {
        $ids = DB::table('taxes')->where('name', self::NAME)->pluck('id');

        DB::table('taxes')->whereIn('id', $ids)->update(['type' => 'final', 'updated_at' => now()]);
        DB::table('income_transactions')->whereIn('tax_id', $ids)->update(['amount' => DB::raw('subtotal')]);
    }

    public function down(): void
    {
        $ids = DB::table('taxes')->where('name', self::NAME)->pluck('id');

        DB::table('taxes')->whereIn('id', $ids)->update(['type' => 'withholding', 'updated_at' => now()]);
        DB::table('income_transactions')->whereIn('tax_id', $ids)->update(['amount' => DB::raw('subtotal - tax_amount')]);
    }
};
