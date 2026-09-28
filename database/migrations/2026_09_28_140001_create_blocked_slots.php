<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_slots', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('start_time', 5);
            $table->string('end_time', 5);
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['date', 'start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_slots');
    }
};
