<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['income_transactions', 'expense_transactions', 'transfers', 'loans', 'loan_repayments', 'tax_payments'];

    public function up(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('proof_path')->nullable();
                $table->string('proof_original_name')->nullable();
                $table->string('proof_url', 2048)->nullable();
            });
        }

        // The old attachment_path column was never written to; proof_path replaces it.
        foreach (['income_transactions', 'expense_transactions'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('attachment_path'));
        }
    }

    public function down(): void
    {
        foreach (['income_transactions', 'expense_transactions'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->string('attachment_path')->nullable());
        }

        foreach (self::TABLES as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn(['proof_path', 'proof_original_name', 'proof_url']));
        }
    }
};
