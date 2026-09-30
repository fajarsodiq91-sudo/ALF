<?php

namespace Tests\Feature;

use App\Mail\ProjectTaskNotification;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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
        $task = ProjectTask::firstOrFail();

        foreach ([
            route('projects.index'),
            route('projects.create'),
            route('projects.show', $project),
            route('projects.edit', $project),
            route('projects.tasks.edit', $task),
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
        Mail::fake();
        $finance = $this->userWithRole('Finance');
        $project = Project::factory()->create();
        $assignee = Employee::factory()->create();

        $this->actingAs($finance)->post(route('projects.tasks.store', $project), [
            'title' => 'Bangun ETL', 'priority' => 'high', 'due_date' => '2026-07-01', 'assignee_ids' => [$assignee->id],
        ])->assertRedirect(route('projects.show', $project));
        $task = ProjectTask::firstOrFail();
        $this->assertSame('todo', $task->status);
        $this->assertSame('high', $task->priority);
        $this->assertSame([$assignee->id], $task->assignees->pluck('id')->all());

        $this->actingAs($finance)->patch(route('projects.tasks.status', $task), ['status' => 'done'])->assertRedirect();
        $this->assertSame('done', $task->fresh()->status);
        $this->actingAs($finance)->patch(route('projects.tasks.status', $task), ['status' => 'bogus'])->assertSessionHasErrors('status');

        $this->actingAs($finance)->delete(route('projects.tasks.destroy', $task))->assertRedirect();
        $this->assertModelMissing($task);
    }

    public function test_new_task_form_defaults_to_today_and_seven_days_later(): void
    {
        $this->travelTo('2026-10-05 09:00:00');
        $project = Project::factory()->create();
        $employee = Employee::factory()->create(['name' => 'Budi Santoso']);

        $this->actingAs($this->userWithRole('Finance'))->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('name="start_date" value="2026-10-05"', false)
            ->assertSee('name="due_date" value="2026-10-12"', false)
            ->assertSee('title="Budi Santoso"', false);

        $task = ProjectTask::factory()->create(['project_id' => $project->id, 'start_date' => '2026-10-01', 'due_date' => null]);
        $this->actingAs($this->userWithRole('Finance'))->get(route('projects.tasks.edit', $task))
            ->assertSee('name="start_date" value="2026-10-01"', false)
            ->assertSee('name="due_date" value=""', false);
    }

    public function test_task_validation(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->userWithRole('Finance'))
            ->post(route('projects.tasks.store', $project), [
                'title' => '', 'priority' => 'bogus', 'start_date' => '2026-07-10', 'due_date' => '2026-07-01', 'assignee_ids' => [999],
            ])
            ->assertSessionHasErrors(['title', 'priority', 'due_date', 'assignee_ids.0']);
    }

    public function test_assignees_are_emailed_when_a_task_is_added(): void
    {
        Mail::fake();
        $project = Project::factory()->create();
        $first = Employee::factory()->create(['email' => 'first@example.com']);
        $second = Employee::factory()->create(['email' => 'second@example.com']);
        $noEmail = Employee::factory()->create(['email' => null]);
        $bystander = Employee::factory()->create(['email' => 'bystander@example.com']);

        $this->actingAs($this->userWithRole('Finance'))->post(route('projects.tasks.store', $project), [
            'title' => 'Siapkan materi', 'priority' => 'medium', 'assignee_ids' => [$first->id, $second->id, $noEmail->id],
        ])->assertRedirect();

        Mail::assertSent(ProjectTaskNotification::class, 2);
        Mail::assertSent(ProjectTaskNotification::class, fn ($mail) => $mail->hasTo('first@example.com') && $mail->type === 'assigned');
        Mail::assertSent(ProjectTaskNotification::class, fn ($mail) => $mail->hasTo('second@example.com'));
        Mail::assertNotSent(ProjectTaskNotification::class, fn ($mail) => $mail->hasTo('bystander@example.com'));
    }

    public function test_task_without_assignees_sends_no_email(): void
    {
        Mail::fake();

        $this->actingAs($this->userWithRole('Finance'))->post(route('projects.tasks.store', Project::factory()->create()), [
            'title' => 'Sendiri', 'priority' => 'low',
        ])->assertRedirect();

        Mail::assertNothingSent();
    }

    public function test_editing_a_task_notifies_current_new_and_removed_assignees(): void
    {
        Mail::fake();
        $stays = Employee::factory()->create(['email' => 'stays@example.com']);
        $leaves = Employee::factory()->create(['email' => 'leaves@example.com']);
        $joins = Employee::factory()->create(['email' => 'joins@example.com']);
        $task = ProjectTask::factory()->create(['title' => 'Lama', 'priority' => 'low']);
        $task->assignees()->sync([$stays->id, $leaves->id]);

        $this->actingAs($this->userWithRole('Finance'))->put(route('projects.tasks.update', $task), [
            'title' => 'Baru', 'priority' => 'urgent', 'status' => 'in_progress', 'due_date' => '2026-12-01',
            'assignee_ids' => [$stays->id, $joins->id],
        ])->assertRedirect(route('projects.show', $task->project_id));

        $task->refresh();
        $this->assertSame('Baru', $task->title);
        $this->assertEqualsCanonicalizing([$stays->id, $joins->id], $task->assignees->pluck('id')->all());

        Mail::assertSent(ProjectTaskNotification::class, 3);
        Mail::assertSent(ProjectTaskNotification::class, fn ($mail) => $mail->hasTo('stays@example.com') && $mail->type === 'updated'
            && $mail->changes['Title'] === ['Lama', 'Baru'] && $mail->changes['Priority'] === ['Low', 'Urgent'] && isset($mail->changes['Assignees']));
        Mail::assertSent(ProjectTaskNotification::class, fn ($mail) => $mail->hasTo('joins@example.com') && $mail->type === 'assigned');
        Mail::assertSent(ProjectTaskNotification::class, fn ($mail) => $mail->hasTo('leaves@example.com') && $mail->type === 'unassigned');
    }

    public function test_status_change_notifies_assignees_and_unchanged_save_does_not(): void
    {
        Mail::fake();
        $employee = Employee::factory()->create(['email' => 'dev@example.com']);
        $task = ProjectTask::factory()->create(['status' => 'todo']);
        $task->assignees()->sync([$employee->id]);
        $finance = $this->userWithRole('Finance');

        $this->actingAs($finance)->patch(route('projects.tasks.status', $task), ['status' => 'done'])->assertRedirect();
        Mail::assertSent(ProjectTaskNotification::class, fn ($mail) => $mail->hasTo('dev@example.com')
            && $mail->changes === ['Status' => ['To Do', 'Done']]);

        $this->actingAs($finance)->patch(route('projects.tasks.status', $task), ['status' => 'done'])->assertRedirect();
        Mail::assertSent(ProjectTaskNotification::class, 1);
    }

    public function test_notification_renders_and_a_mail_failure_does_not_block_saving(): void
    {
        $employee = Employee::factory()->create(['email' => 'dev@example.com']);
        $task = ProjectTask::factory()->create(['description' => 'Detail tugas']);

        $html = (new ProjectTaskNotification($task, $employee, 'updated', ['Status' => ['To Do', 'Done']]))->render();
        $this->assertStringContainsString($task->title, $html);
        $this->assertStringContainsString('Detail tugas', $html);

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));
        $this->actingAs($this->userWithRole('Finance'))->post(route('projects.tasks.store', $task->project), [
            'title' => 'Tetap tersimpan', 'priority' => 'medium', 'assignee_ids' => [$employee->id],
        ])->assertRedirect();
        $this->assertDatabaseHas('project_tasks', ['title' => 'Tetap tersimpan']);
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
        $this->actingAs($user)->patch(route('projects.tasks.status', $task), ['status' => 'done'])->assertForbidden();
        $this->actingAs($user)->get(route('projects.tasks.edit', $task))->assertForbidden();
        $this->actingAs($user)->put(route('projects.tasks.update', $task), ['title' => 'X', 'priority' => 'low'])->assertForbidden();
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
