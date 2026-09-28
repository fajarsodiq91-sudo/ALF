<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_session_payments', function (Blueprint $table) {
            $table->boolean('due_after_completion')->default(false)->after('due_meeting_number');
            $table->timestamp('invoice_sent_at')->nullable()->after('paid_date');
        });
    }

    public function down(): void
    {
        Schema::table('training_session_payments', function (Blueprint $table) {
            $table->dropColumn(['due_after_completion', 'invoice_sent_at']);
        });
    }
};
