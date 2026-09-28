<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** Customers who registered themselves before status links existed get one, so their old thank-you page keeps working. */
    public function up(): void
    {
        DB::table('customers')->whereNotNull('submitted_at')->whereNull('status_token')->orderBy('id')->each(function ($customer) {
            DB::table('customers')->where('id', $customer->id)->update(['status_token' => Str::random(40)]);
        });
    }

    public function down(): void
    {
        // Nothing to undo: the tokens are harmless and the column is dropped by its own migration.
    }
};
