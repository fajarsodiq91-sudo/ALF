<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HrAttendanceTest extends TestCase
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

    public function test_pages_render(): void
    {
        $finance = $this->userWithRole('Finance');
        $record = AttendanceRecord::factory()->create(['attendance_date' => now()->format('Y-m-d')]);

        $this->actingAs($finance)->get(route('hr.attendance.index'))->assertOk()->assertSee($record->employee->name);
        $this->actingAs($finance)->get(route('hr.attendance.create'))->assertOk();
        $this->actingAs($finance)->get(route('hr.attendance.edit', $record))->assertOk();
        $this->actingAs($this->userWithRole('Staff'))->get(route('hr.attendance.index'))->assertForbidden();
    }

    public function test_create_update_delete_and_duplicate_guard(): void
    {
        $finance = $this->userWithRole('Finance');
        $employee = Employee::factory()->create();
        $data = ['employee_id' => $employee->id, 'attendance_date' => '2026-03-02', 'status' => 'present', 'check_in' => '08:05', 'check_out' => '17:00'];

        $this->actingAs($finance)->post(route('hr.attendance.store'), $data)->assertRedirect();
        $record = AttendanceRecord::firstOrFail();

        $this->actingAs($finance)->post(route('hr.attendance.store'), $data)->assertSessionHasErrors('attendance_date');

        $this->actingAs($finance)->put(route('hr.attendance.update', $record), [...$data, 'status' => 'sick', 'check_in' => null, 'check_out' => null])->assertRedirect();
        $this->assertSame('sick', $record->fresh()->status);

        $this->actingAs($finance)->delete(route('hr.attendance.destroy', $record))->assertRedirect();
        $this->assertModelMissing($record);
    }

    public function test_monthly_recap_counts_statuses(): void
    {
        $employee = Employee::factory()->create();
        AttendanceRecord::factory()->create(['employee_id' => $employee->id, 'attendance_date' => '2026-03-02', 'status' => 'present']);
        AttendanceRecord::factory()->create(['employee_id' => $employee->id, 'attendance_date' => '2026-03-03', 'status' => 'absent']);
        AttendanceRecord::factory()->create(['employee_id' => $employee->id, 'attendance_date' => '2026-04-01', 'status' => 'sick']);

        $response = $this->actingAs($this->userWithRole('Finance'))->get(route('hr.attendance.index', ['month' => '2026-03']));

        $response->assertOk();
        $this->assertCount(2, $response->viewData('records'));
        $this->assertSame(1, $response->viewData('recap')[$employee->id]['absent']);
    }
}
