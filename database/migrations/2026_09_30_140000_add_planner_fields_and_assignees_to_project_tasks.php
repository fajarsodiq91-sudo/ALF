<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
            $table->string('priority')->default('medium')->after('status');
            $table->date('start_date')->nullable()->after('priority');
        });

        Schema::create('employee_project_task', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['project_task_id', 'employee_id']);
        });

        DB::table('project_tasks')->whereNotNull('assignee_id')->orderBy('id')->each(function ($task) {
            DB::table('employee_project_task')->insert([
                'project_task_id' => $task->id,
                'employee_id' => $task->assignee_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assignee_id');
        });
    }

    public function down(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->foreignId('assignee_id')->nullable()->constrained('employees')->nullOnDelete();
        });

        DB::table('employee_project_task')->orderBy('id')->each(function ($row) {
            DB::table('project_tasks')->where('id', $row->project_task_id)->whereNull('assignee_id')->update(['assignee_id' => $row->employee_id]);
        });

        Schema::dropIfExists('employee_project_task');

        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropColumn(['description', 'priority', 'start_date']);
        });
    }
};
