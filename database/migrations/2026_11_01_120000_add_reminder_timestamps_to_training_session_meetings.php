<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_session_meetings', function (Blueprint $table) {
            $table->timestamp('day_before_reminded_at')->nullable()->after('completed_at');
            $table->timestamp('hour_before_reminded_at')->nullable()->after('day_before_reminded_at');
        });
    }

    public function down(): void
    {
        Schema::table('training_session_meetings', function (Blueprint $table) {
            $table->dropColumn(['day_before_reminded_at', 'hour_before_reminded_at']);
        });
    }
};
