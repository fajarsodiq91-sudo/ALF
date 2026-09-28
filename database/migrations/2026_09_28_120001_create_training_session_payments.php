<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_sessions', function (Blueprint $table) {
            $table->string('payment_plan', 20)->default('full')->after('fee');
        });

        Schema::create('training_session_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->decimal('percentage', 5, 2);
            $table->decimal('amount', 15, 2);
            $table->unsignedSmallInteger('due_meeting_number')->nullable();
            $table->date('paid_date')->nullable();
            $table->foreignId('income_transaction_id')->nullable()->constrained('income_transactions')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_session_payments');

        Schema::table('training_sessions', function (Blueprint $table) {
            $table->dropColumn('payment_plan');
        });
    }
};
