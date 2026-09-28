<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_code_sequences', function (Blueprint $table) {
            $table->string('period', 4)->primary();
            $table->unsignedSmallInteger('last_number')->default(0);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('customer_code', 6)->nullable()->unique()->after('id');
        });

        // Existing customers get an ID from the month they were registered, oldest first.
        $counters = [];
        foreach (DB::table('customers')->orderBy('created_at')->orderBy('id')->get(['id', 'created_at']) as $customer) {
            $period = Carbon::parse($customer->created_at)->format('ym');
            $counters[$period] = ($counters[$period] ?? 0) + 1;

            DB::table('customers')->where('id', $customer->id)->update([
                'customer_code' => $period.str_pad((string) $counters[$period], 2, '0', STR_PAD_LEFT),
            ]);
        }

        foreach ($counters as $period => $last) {
            DB::table('customer_code_sequences')->insert(['period' => $period, 'last_number' => $last]);
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['customer_code']);
            $table->dropColumn('customer_code');
        });

        Schema::dropIfExists('customer_code_sequences');
    }
};
