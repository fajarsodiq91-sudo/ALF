<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TrainingTest extends TestCase
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
    private function sessionPayload(TrainingProgram $program, array $overrides = []): array
    {
        return [
            'training_program_id' => $program->id,
            'customer_id' => Customer::factory()->create()->id,
            'start_date' => '2026-05-04',
            'end_date' => '2026-05-05',
            'delivery_mode' => 'onsite',
            'participants_count' => 15,
            'fee' => 12000000,
            'status' => 'planned',
            ...$overrides,
        ];
    }

    public function test_all_pages_render_for_finance_and_are_blocked_for_staff(): void
    {
        $finance = $this->userWithRole('Finance');
        $session = TrainingSession::factory()->create();

        foreach ([
            route('training.index'),
            route('training.create'),
            route('training.edit', $session),
            route('training.programs.index'),
            route('training.programs.create'),
            route('training.programs.edit', $session->program),
        ] as $url) {
            $this->actingAs($finance)->get($url)->assertOk();
        }

        $this->actingAs($this->userWithRole('Staff'))->get(route('training.index'))->assertForbidden();
        $this->actingAs($this->userWithRole('Staff'))->get(route('training.programs.index'))->assertForbidden();
    }

    public function test_program_crud_and_delete_guard(): void
    {
        $finance = $this->userWithRole('Finance');

        $this->actingAs($finance)->post(route('training.programs.store'), [
            'name' => 'Power BI Dasar', 'duration_days' => 2, 'standard_price' => 5000000, 'is_active' => '1',
        ])->assertRedirect(route('training.programs.index'));
        $program = TrainingProgram::firstOrFail();

        $this->actingAs($finance)->put(route('training.programs.update', $program), [
            'name' => 'Power BI Lanjut', 'duration_days' => 3, 'standard_price' => 7000000, 'is_active' => '0',
        ])->assertRedirect(route('training.programs.index'));
        $this->assertFalse($program->fresh()->is_active);

        TrainingSession::factory()->create(['training_program_id' => $program->id]);
        $this->actingAs($finance)->delete(route('training.programs.destroy', $program))->assertSessionHas('error');
        $this->assertModelExists($program);

        $empty = TrainingProgram::factory()->create();
        $this->actingAs($finance)->delete(route('training.programs.destroy', $empty))->assertSessionHas('status');
        $this->assertModelMissing($empty);
    }

    public function test_session_crud_with_optional_customer_and_instructor(): void
    {
        $finance = $this->userWithRole('Finance');
        $program = TrainingProgram::factory()->create();
        $instructor = Employee::factory()->create();

        $this->actingAs($finance)->post(route('training.store'), $this->sessionPayload($program, [
            'customer_id' => null, 'instructor_id' => $instructor->id,
        ]))->assertRedirect(route('training.index'));
        $session = TrainingSession::firstOrFail();
        $this->assertNull($session->customer_id);

        $this->actingAs($finance)->put(route('training.update', $session), $this->sessionPayload($program, ['status' => 'completed']))
            ->assertRedirect(route('training.index'));
        $this->assertSame('completed', $session->fresh()->status);

        $this->actingAs($finance)->delete(route('training.destroy', $session))->assertRedirect(route('training.index'));
        $this->assertModelMissing($session);
    }

    public function test_session_validation(): void
    {
        $program = TrainingProgram::factory()->create();

        $this->actingAs($this->userWithRole('Finance'))
            ->post(route('training.store'), $this->sessionPayload($program, [
                'end_date' => '2026-05-01', 'delivery_mode' => 'bogus', 'fee' => -1,
            ]))
            ->assertSessionHasErrors(['end_date', 'delivery_mode', 'fee']);
    }

    public function test_index_filters_by_status_and_excludes_cancelled_from_total(): void
    {
        TrainingSession::factory()->create(['status' => 'completed', 'fee' => 1000000, 'location' => 'Jakarta']);
        TrainingSession::factory()->create(['status' => 'cancelled', 'fee' => 9000000, 'location' => 'Bandung']);

        $finance = $this->userWithRole('Finance');
        $all = $this->actingAs($finance)->get(route('training.index'));
        $this->assertSame(1000000.0, $all->viewData('totalFee'));

        $this->actingAs($finance)->get(route('training.index', ['status' => 'cancelled']))
            ->assertSee('Bandung')
            ->assertDontSee('Jakarta');
    }

    public function test_view_only_user_cannot_manage_training(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['access-erp', 'training.view']);
        $session = TrainingSession::factory()->create();

        $this->actingAs($user)->get(route('training.index'))->assertOk();
        $this->actingAs($user)->get(route('training.create'))->assertForbidden();
        $this->actingAs($user)->delete(route('training.destroy', $session))->assertForbidden();
        $this->actingAs($user)->post(route('training.programs.store'), ['name' => 'X'])->assertForbidden();
    }

    public function test_customer_and_employee_with_linked_records_cannot_be_deleted(): void
    {
        $finance = $this->userWithRole('Finance');
        $session = TrainingSession::factory()->create();

        $this->actingAs($finance)->delete(route('sales.destroy', $session->customer))->assertSessionHas('error');
        $this->assertModelExists($session->customer);

        $employee = Employee::factory()->create();
        Payroll::factory()->create(['employee_id' => $employee->id]);
        $this->actingAs($finance)->delete(route('hr.destroy', $employee))->assertSessionHas('error');
        $this->assertModelExists($employee);
    }
}
