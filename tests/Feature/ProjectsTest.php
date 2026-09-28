<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProjectsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'code' => 'PRJ-2026-001',
            'name' => 'Dashboard Penjualan',
            'customer_id' => Customer::factory()->create()->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-08-31',
            'contract_value' => 75000000,
            'status' => 'planned',
            ...$overrides,
        ];
    }

    public function test_all_pages_render_and_staff_is_blocked(): void
    {
        $finance = $this->userWithRole('Finance');
        $project = Project::factory()->create();
        ProjectTask::factory()->create(['project_id' => $project->id]);

        foreach ([
            route('projects.index'),
            route('projects.create'),
            route('projects.show', $project),
            route('projects.edit', $project),
        ] as $url) {
            $this->actingAs($finance)->get($url)->assertOk();
        }

        $this->actingAs($this->userWithRole('Staff'))->get(route('projects.index'))->assertForbidden();
    }

    public function test_project_crud_and_unique_code(): void
    {
        $finance = $this->userWithRole('Finance');

        $this->actingAs($finance)->post(route('projects.store'), $this->payload())->assertRedirect();
        $project = Project::firstOrFail();

        $this->actingAs($finance)->post(route('projects.store'), $this->payload())->assertSessionHasErrors('code');

        $this->actingAs($finance)->put(route('projects.update', $project), $this->payload(['status' => 'in_progress']))
            ->assertRedirect(route('projects.show', $project));
        $this->assertSame('in_progress', $project->fresh()->status);

        $this->actingAs($finance)->delete(route('projects.destroy', $project))->assertRedirect(route('projects.index'));
        $this->assertModelMissing($project);
    }

    public function test_project_validation(): void
    {
        $this->actingAs($this->userWithRole('Finance'))
            ->post(route('projects.store'), $this->payload(['end_date' => '2026-05-01', 'status' => 'bogus', 'contract_value' => -5]))
            ->assertSessionHasErrors(['end_date', 'status', 'contract_value']);
    }

    public function test_progress_is_share_of_done_tasks(): void
    {
        $project = Project::factory()->create();
        ProjectTask::factory()->count(3)->create(['project_id' => $project->id, 'status' => 'done']);
        ProjectTask::factory()->create(['project_id' => $project->id, 'status' => 'todo']);

        $this->assertSame(75, $project->progressPercent());
        $this->assertSame(0, Project::factory()->create()->progressPercent());

        $listed = $this->actingAs($this->userWithRole('Finance'))->get(route('projects.index'))->viewData('projects');
        $this->assertSame(75, $listed->firstWhere('id', $project->id)->progressPercent());
    }

    public function test_overdue_flag(): void
    {
        $this->assertTrue(Project::factory()->make(['end_date' => now()->subDays(3), 'status' => 'in_progress'])->isOverdue());
        $this->assertFalse(Project::factory()->make(['end_date' => now()->subDays(3), 'status' => 'completed'])->isOverdue());
        $this->assertFalse(Project::factory()->make(['end_date' => now()->addDays(3), 'status' => 'in_progress'])->isOverdue());
    }

    public function test_task_add_status_change_and_delete(): void
    {
        $finance = $this->userWithRole('Finance');
        $project = Project::factory()->create();
        $assignee = Employee::factory()->create();

        $this->actingAs($finance)->post(route('projects.tasks.store', $project), [
            'title' => 'Bangun ETL', 'assignee_id' => $assignee->id, 'due_date' => '2026-07-01',
        ])->assertRedirect(route('projects.show', $project));
        $task = ProjectTask::firstOrFail();
        $this->assertSame('todo', $task->status);

        $this->actingAs($finance)->patch(route('projects.tasks.update', $task), ['status' => 'done'])->assertRedirect();
        $this->assertSame('done', $task->fresh()->status);
        $this->actingAs($finance)->patch(route('projects.tasks.update', $task), ['status' => 'bogus'])->assertSessionHasErrors('status');

        $this->actingAs($finance)->delete(route('projects.tasks.destroy', $task))->assertRedirect();
        $this->assertModelMissing($task);
    }

    public function test_view_only_user_cannot_manage_projects_or_tasks(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['access-erp', 'projects.view']);
        $project = Project::factory()->create();
        $task = ProjectTask::factory()->create(['project_id' => $project->id]);

        $this->actingAs($user)->get(route('projects.show', $project))->assertOk();
        $this->actingAs($user)->get(route('projects.create'))->assertForbidden();
        $this->actingAs($user)->post(route('projects.tasks.store', $project), ['title' => 'X'])->assertForbidden();
        $this->actingAs($user)->patch(route('projects.tasks.update', $task), ['status' => 'done'])->assertForbidden();
        $this->actingAs($user)->delete(route('projects.destroy', $project))->assertForbidden();
    }

    public function test_customer_with_projects_cannot_be_deleted(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->userWithRole('Finance'))
            ->delete(route('sales.destroy', $project->customer))
            ->assertSessionHas('error');
        $this->assertModelExists($project->customer);
    }
}
