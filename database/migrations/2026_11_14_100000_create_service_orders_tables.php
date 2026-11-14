<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Which form a customer invited by QR code fills in: 'learning' (programs and sessions) or 'service' (custom work).
        Schema::table('customers', function (Blueprint $table) {
            $table->string('registration_track', 20)->default('learning')->after('registration_status');
        });

        // The catalog of custom-work packages (e.g. an Excel dashboard), each with optional priced add-ons.
        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('features')->nullable();
            $table->decimal('starting_price', 15, 2)->default(0);
            $table->unsignedSmallInteger('included_revisions')->default(2);
            $table->unsignedSmallInteger('estimated_days')->nullable();
            $table->json('addons')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('brief');

            // The brief the customer fills in.
            $table->string('title');
            $table->text('purpose')->nullable();
            $table->text('kpis')->nullable();
            $table->text('data_sources')->nullable();
            $table->string('data_volume')->nullable();
            $table->string('excel_version', 100)->nullable();
            $table->boolean('needs_refresh')->default(false);
            $table->date('deadline')->nullable();
            $table->text('notes')->nullable();
            $table->json('addons')->nullable();

            // The quote the company sends back.
            $table->json('quote_items')->nullable();
            $table->decimal('quote_total', 15, 2)->nullable();
            $table->unsignedSmallInteger('dp_percentage')->default(50);
            $table->unsignedSmallInteger('estimated_days')->nullable();
            $table->unsignedSmallInteger('included_revisions')->default(2);
            $table->date('quote_valid_until')->nullable();
            $table->text('quote_notes')->nullable();

            $table->text('decline_reason')->nullable();
            $table->timestamp('quoted_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
        });

        Schema::create('service_order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 10); // dp, final or extra
            $table->string('label');
            $table->decimal('amount', 15, 2);
            $table->timestamp('invoice_sent_at')->nullable();
            $table->date('paid_date')->nullable();
            $table->foreignId('income_transaction_id')->nullable()->constrained('income_transactions')->nullOnDelete();
            $table->string('proof_path')->nullable();
            $table->string('proof_original_name')->nullable();
            $table->string('proof_url', 2048)->nullable();
            $table->timestamps();
        });

        Schema::create('service_order_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 10); // brief (from the customer), draft or final (from the company)
            $table->string('path');
            $table->string('original_name');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('service_order_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->boolean('is_extra')->default(false); // beyond the revisions the quote included
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_order_revisions');
        Schema::dropIfExists('service_order_files');
        Schema::dropIfExists('service_order_payments');
        Schema::dropIfExists('service_orders');
        Schema::dropIfExists('service_packages');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('registration_track');
        });
    }
};
