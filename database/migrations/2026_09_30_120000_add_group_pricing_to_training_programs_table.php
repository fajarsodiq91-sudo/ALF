<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_programs', function (Blueprint $table) {
            $table->unsignedTinyInteger('group_max_size')->nullable()->after('standard_price');
            $table->json('group_tiers')->nullable()->after('group_max_size');
        });
    }

    public function down(): void
    {
        Schema::table('training_programs', function (Blueprint $table) {
            $table->dropColumn(['group_max_size', 'group_tiers']);
        });
    }
};
