<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->string('suffix', 3)->unique();
            $table->date('issued_at');
            $table->timestamps();

            $table->unique(['training_session_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
