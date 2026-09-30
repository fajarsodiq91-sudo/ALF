<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Repeating content of the public company site (service cards, testimonials, …), told apart by `type` like master_data's `group`. */
    public function up(): void
    {
        Schema::create('site_items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30);
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('body')->nullable();
            $table->string('icon', 32)->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('link_url', 500)->nullable();
            $table->string('category', 100)->nullable();
            $table->unsignedInteger('number')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_items');
    }
};
