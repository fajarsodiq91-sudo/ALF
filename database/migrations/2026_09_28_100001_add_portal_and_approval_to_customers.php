<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn([
                'password', 'remember_token', 'must_change_password', 'submitted_at',
                'approved_at', 'rejected_at', 'rejection_reason',
            ]);
        });
    }
};
