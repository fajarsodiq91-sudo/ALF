<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tap_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('token_hash', 64)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('training_meeting_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->timestamp('checked_in_at');
            $table->string('method', 10);
            $table->foreignId('tap_device_id')->nullable()->constrained('tap_devices')->nullOnDelete();
            $table->timestamps();

            $table->unique(['training_session_meeting_id', 'customer_id'], 'meeting_attendance_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_meeting_attendances');
        Schema::dropIfExists('tap_devices');
    }
};
