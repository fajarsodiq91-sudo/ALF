<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_number')->unique();
            $table->string('direction');
            $table->date('loan_date');
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('party_name');
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('loan_date');
        });

        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->string('repayment_number')->unique();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->date('repayment_date');
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('repayment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
        Schema::dropIfExists('loans');
    }
};
