<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_programs', function (Blueprint $table) {
            $table->string('program_type', 100)->default('learning')->after('name');
        });

        Schema::table('training_sessions', function (Blueprint $table) {
            $table->string('materials_url', 2048)->nullable()->after('notes');
            $table->string('certificate_url', 2048)->nullable()->after('materials_url');
        });

        Schema::create('training_session_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained()->cascadeOnDelete();
            $table->date('meeting_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location')->nullable();
            $table->string('topic')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('meeting_date');
        });

        Schema::create('customer_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('external_url', 2048)->nullable();
            $table->boolean('in_portfolio')->default(false);
            $table->timestamps();
        });

        $now = now();
        foreach (['learning' => 'Learning', 'consulting' => 'Consulting'] as $i => $label) {
            DB::table('master_data')->insert([
                'group' => 'program_type', 'code' => $i, 'label' => $label,
                'sort_order' => $i === 'learning' ? 1 : 2, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('master_data')->where('group', 'program_type')->delete();
        Schema::dropIfExists('customer_projects');
        Schema::dropIfExists('training_session_meetings');

        Schema::table('training_sessions', function (Blueprint $table) {
            $table->dropColumn(['materials_url', 'certificate_url']);
        });

        Schema::table('training_programs', function (Blueprint $table) {
            $table->dropColumn('program_type');
        });
    }
};
