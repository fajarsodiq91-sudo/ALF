<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('registration_status', 30)->default('complete')->after('customer_type');
            $table->string('registration_token', 64)->nullable()->unique()->after('registration_status');
            $table->timestamp('registration_token_expires_at')->nullable()->after('registration_token');
            $table->string('photo_path')->nullable()->after('notes');

            $table->index('registration_status');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['registration_status']);
            $table->dropUnique(['registration_token']);
            $table->dropColumn(['registration_status', 'registration_token', 'registration_token_expires_at', 'photo_path']);
        });
    }
};
