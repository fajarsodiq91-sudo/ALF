<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HrLeaveTest extends TestCase
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
    private function payload(Employee $employee, array $overrides = []): array
    {
        return [
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-03-02',
            'end_date' => '2026-03-06',
            ...$overrides,
        ];
    }

    public function test_pages_render_and_staff_is_blocked(): void
    {
        $finance = $this->userWithRole('Finance');
        Employee::factory()->create();
        LeaveRequest::factory()->create();

        $this->actingAs($finance)->get(route('hr.leaves.index'))->assertOk()->assertSee('Annual leave balance');
        $this->actingAs($finance)->get(route('hr.leaves.create'))->assertOk();
        $this->actingAs($this->userWithRole('Staff'))->get(route('hr.leaves.index'))->assertForbidden();
    }

    public function test_submit_counts_only_working_days(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($this->userWithRole('Finance'))
            ->post(route('hr.leaves.store'), $this->payload($employee, ['start_date' => '2026-03-06', 'end_date' => '2026-03-09']))
            ->assertRedirect(route('hr.leaves.index'));

        $this->assertSame(2, LeaveRequest::first()->days);
        $this->assertSame('pending', LeaveRequest::first()->status);
    }

    public function test_weekend_only_request_is_rejected(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($this->userWithRole('Finance'))
            ->post(route('hr.leaves.store'), $this->payload($employee, ['start_date' => '2026-03-07', 'end_date' => '2026-03-08']))
            ->assertSessionHasErrors('start_date');
    }

    public function test_annual_leave_cannot_exceed_balance(): void
    {
        $employee = Employee::factory()->create(['annual_leave_quota' => 3]);

        $this->actingAs($this->userWithRole('Finance'))
            ->post(route('hr.leaves.store'), $this->payload($employee))
            ->assertSessionHasErrors('leave_type');

        $this->actingAs($this->userWithRole('Finance'))
            ->post(route('hr.leaves.store'), $this->payload($employee, ['leave_type' => 'sick']))
            ->assertSessionHasNoErrors();
    }

    public function test_approve_reduces_balance_and_reject_does_not(): void
    {
        $employee = Employee::factory()->create(['annual_leave_quota' => 12]);
        $approved = LeaveRequest::factory()->create(['employee_id' => $employee->id]);
        $rejected = LeaveRequest::factory()->create(['employee_id' => $employee->id, 'start_date' => '2026-04-06', 'end_date' => '2026-04-08']);
        $finance = $this->userWithRole('Finance');

        $this->actingAs($finance)->post(route('hr.leaves.approve', $approved))->assertRedirect();
        $this->actingAs($finance)->post(route('hr.leaves.reject', $rejected))->assertRedirect();

        $this->assertSame('approved', $approved->fresh()->status);
        $this->assertSame('rejected', $rejected->fresh()->status);
        $this->assertSame(9, $employee->annualLeaveRemaining(2026));
    }

    public function test_cannot_approve_beyond_balance(): void
    {
        $employee = Employee::factory()->create(['annual_leave_quota' => 2]);
        $leave = LeaveRequest::factory()->create(['employee_id' => $employee->id, 'days' => 3]);

        $this->actingAs($this->userWithRole('Finance'))->post(route('hr.leaves.approve', $leave))
            ->assertSessionHas('error');
        $this->assertSame('pending', $leave->fresh()->status);
    }

    public function test_view_only_user_cannot_manage_leave(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['access-erp', 'hr.view']);
        $leave = LeaveRequest::factory()->create();

        $this->actingAs($user)->get(route('hr.leaves.index'))->assertOk();
        $this->actingAs($user)->post(route('hr.leaves.approve', $leave))->assertForbidden();
        $this->actingAs($user)->delete(route('hr.leaves.destroy', $leave))->assertForbidden();
    }
}
