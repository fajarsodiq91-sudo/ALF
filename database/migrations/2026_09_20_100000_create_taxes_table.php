<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->decimal('rate', 5, 2);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('taxes')->insert([
            ['name' => 'PPN 11%', 'type' => 'vat', 'rate' => 11, 'is_active' => true, 'description' => 'Pajak Pertambahan Nilai', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'PPN 12%', 'type' => 'vat', 'rate' => 12, 'is_active' => true, 'description' => 'Pajak Pertambahan Nilai', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'PPh 23 (2%)', 'type' => 'withholding', 'rate' => 2, 'is_active' => true, 'description' => 'Pajak Penghasilan Pasal 23 (jasa)', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'PPh Final UMKM (0,5%)', 'type' => 'withholding', 'rate' => 0.5, 'is_active' => true, 'description' => 'PPh final UMKM', 'created_at' => $now, 'updated_at' => $now],
        ]);

        foreach (['income_transactions', 'expense_transactions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->decimal('subtotal', 15, 2)->default(0)->after('description');
                $table->foreignId('tax_id')->nullable()->after('subtotal')->constrained('taxes')->restrictOnDelete();
                $table->decimal('tax_rate', 5, 2)->nullable()->after('tax_id');
                $table->decimal('tax_amount', 15, 2)->default(0)->after('tax_rate');
            });

            DB::table($tableName)->update(['subtotal' => DB::raw('amount')]);
        }
    }

    public function down(): void
    {
        foreach (['income_transactions', 'expense_transactions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('tax_id');
                $table->dropColumn(['subtotal', 'tax_rate', 'tax_amount']);
            });
        }

        Schema::dropIfExists('taxes');
    }
};
