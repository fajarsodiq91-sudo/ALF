<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_transactions', function (Blueprint $table) {
            $table->foreignId('transfer_id')->nullable()->after('tax_amount')->constrained('transfers')->cascadeOnDelete();
        });

        $exists = DB::table('categories')->where('name', 'Bank Charges')->where('type', 'expense')->exists();
        if (! $exists) {
            DB::table('categories')->insert([
                'name' => 'Bank Charges',
                'type' => 'expense',
                'is_active' => true,
                'description' => 'Bank admin fees and account charges',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('expense_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transfer_id');
        });
    }
};
