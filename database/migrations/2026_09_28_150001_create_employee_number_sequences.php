<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_number_sequences', function (Blueprint $table) {
            $table->string('period', 4)->primary();
            $table->unsignedSmallInteger('last_number')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_number_sequences');
    }
};
