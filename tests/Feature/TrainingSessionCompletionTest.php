<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TrainingSessionCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Carbon::setTestNow('2026-09-29 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function endedSession(string $status = 'ongoing'): TrainingSession
    {
        return TrainingSession::factory()->create([
            'training_program_id' => TrainingProgram::factory()->create(['program_type' => 'learning'])->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-02',
            'status' => $status,
        ]);
    }

    public function test_ended_sessions_show_up_as_a_dashboard_reminder(): void
    {
        $this->endedSession();
        $user = $this->userWithRole('Finance');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Needs your attention')
            ->assertSee('Training sessions')
            ->assertSee('ended, mark as done');
    }

    public function test_the_reminder_does_not_count_sessions_already_finished(): void
    {
        $this->endedSession('completed');
        $this->endedSession('cancelled');
        TrainingSession::factory()->create(['start_date' => '2026-12-01', 'end_date' => '2026-12-02', 'status' => 'planned']); // not ended yet
        $user = $this->userWithRole('Finance');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Needs your attention');
    }

    public function test_the_reminder_is_hidden_without_training_manage_permission(): void
    {
        $this->endedSession();
        $user = User::factory()->create();
        $user->givePermissionTo(['access-erp', 'training.view']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Needs your attention');
    }

    public function test_the_ready_to_complete_filter_only_lists_ended_unfinished_sessions(): void
    {
        $ended = $this->endedSession();
        $this->endedSession('completed');
        $upcoming = TrainingSession::factory()->create(['start_date' => '2026-12-01', 'end_date' => '2026-12-02', 'status' => 'planned']);
        $user = $this->userWithRole('Finance');

        $response = $this->actingAs($user)->get(route('training.index', ['ready_to_complete' => 1]));

        // The program dropdown filter always lists every program, so check the row action instead.
        $response->assertOk()
            ->assertSee(route('training.complete', $ended), false)
            ->assertDontSee(route('training.complete', $upcoming), false);
    }

    public function test_mark_as_done_completes_the_session_and_issues_a_certificate(): void
    {
        $session = $this->endedSession();
        $user = $this->userWithRole('Finance');

        $this->actingAs($user)->post(route('training.complete', $session))
            ->assertRedirect(route('training.index'));

        $this->assertSame('completed', $session->fresh()->status);
        $this->assertSame(1, Certificate::where('training_session_id', $session->id)->count());
    }

    public function test_mark_as_done_refuses_a_session_already_finished(): void
    {
        $session = $this->endedSession('completed');
        $user = $this->userWithRole('Finance');

        $this->actingAs($user)->post(route('training.complete', $session))
            ->assertSessionHas('error');

        $this->assertSame(0, Certificate::where('training_session_id', $session->id)->count());
    }

    public function test_mark_as_done_requires_training_manage(): void
    {
        $session = $this->endedSession();
        $user = User::factory()->create();
        $user->givePermissionTo(['access-erp', 'training.view']);

        $this->actingAs($user)->post(route('training.complete', $session))->assertForbidden();
    }
}
