<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_programs', function (Blueprint $table) {
            $table->string('discount_type')->nullable()->after('standard_price');
            $table->decimal('discount_value', 15, 2)->nullable()->after('discount_type');
            $table->date('discount_expires_at')->nullable()->after('discount_value');
        });
    }

    public function down(): void
    {
        Schema::table('training_programs', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value', 'discount_expires_at']);
        });
    }
};
