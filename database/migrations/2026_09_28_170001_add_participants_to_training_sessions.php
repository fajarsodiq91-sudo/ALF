<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_sessions', function (Blueprint $table) {
            $table->unsignedSmallInteger('participant_limit')->nullable()->after('participants_count');
            $table->string('participant_token', 40)->nullable()->unique()->after('participant_limit');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('company_customer_id')->nullable()->after('id')->constrained('customers')->restrictOnDelete();
        });

        Schema::create('training_session_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['training_session_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_session_participants');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_customer_id');
        });

        Schema::table('training_sessions', function (Blueprint $table) {
            $table->dropColumn(['participant_limit', 'participant_token']);
        });
    }
};
