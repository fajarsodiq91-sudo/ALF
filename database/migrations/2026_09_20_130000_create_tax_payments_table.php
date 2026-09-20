<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();
            $table->date('payment_date');
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('tax_type');
            $table->string('period', 7);
            $table->decimal('amount', 15, 2);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['tax_type', 'period']);
        });

        Schema::table('expense_transactions', function (Blueprint $table) {
            $table->foreignId('tax_payment_id')->nullable()->after('transfer_id')->constrained('tax_payments')->cascadeOnDelete();
        });

        if (! DB::table('categories')->where('name', 'Tax Payments')->where('type', 'expense')->exists()) {
            DB::table('categories')->insert([
                'name' => 'Tax Payments',
                'type' => 'expense',
                'is_active' => true,
                'description' => 'Tax remitted to the tax office (PPN, PPh)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('expense_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tax_payment_id');
        });

        Schema::dropIfExists('tax_payments');
    }
};
