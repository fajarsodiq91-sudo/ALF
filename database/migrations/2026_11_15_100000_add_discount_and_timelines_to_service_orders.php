<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Turnaround options of a package: how many working days, and the price change that comes with it.
        Schema::table('service_packages', function (Blueprint $table) {
            $table->json('timelines')->nullable()->after('addons');
        });

        Schema::table('service_orders', function (Blueprint $table) {
            // The turnaround the customer picked, with its price change fixed as listed on that day.
            $table->unsignedSmallInteger('timeline_days')->nullable()->after('addons');
            $table->decimal('timeline_price', 15, 2)->nullable()->after('timeline_days');

            // Optional discount on the quote: a fixed amount or a percentage of the subtotal.
            $table->string('discount_type', 10)->nullable()->after('quote_total');
            $table->decimal('discount_value', 15, 2)->nullable()->after('discount_type');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('discount_value');
            $table->string('discount_label', 100)->nullable()->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn(['timeline_days', 'timeline_price', 'discount_type', 'discount_value', 'discount_amount', 'discount_label']);
        });

        Schema::table('service_packages', function (Blueprint $table) {
            $table->dropColumn('timelines');
        });
    }
};
