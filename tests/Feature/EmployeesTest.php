<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EmployeesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Storage::fake('public');
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
    // 1x1 transparent PNG: keeps these tests independent of GD, which this server does not have.
    private function pngBytes(): string
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
        );
    }

    private function payload(array $overrides = []): array
    {
        return [
            'employee_number' => 'EMP-001',
            'name' => 'Siti Rahma',
            'position' => 'Developer',
            'employment_type' => 'permanent',
            'status' => 'active',
            'join_date' => '2026-02-01',
            'annual_leave_quota' => 12,
            ...$overrides,
        ];
    }

    public function test_finance_role_can_list_employees(): void
    {
        Employee::factory()->create(['name' => 'Andi Pratama']);

        $this->actingAs($this->userWithRole('Finance'))
            ->get(route('hr.index'))
            ->assertOk()
            ->assertSee('Andi Pratama');
    }

    public function test_staff_without_permission_cannot_view_hr(): void
    {
        $this->actingAs($this->userWithRole('Staff'))
            ->get(route('hr.index'))
            ->assertForbidden();
    }

    public function test_create_and_edit_forms_render(): void
    {
        $user = $this->userWithRole('Finance');
        $employee = Employee::factory()->create();

        $this->actingAs($user)->get(route('hr.create'))->assertOk()->assertSee('Create Employee');
        $this->actingAs($user)->get(route('hr.edit', $employee))->assertOk()->assertSee('Update Employee');
    }

    public function test_manager_can_create_update_and_delete_employee(): void
    {
        $user = $this->userWithRole('Finance');

        $this->actingAs($user)->post(route('hr.store'), $this->payload())
            ->assertRedirect(route('hr.index'));
        $employee = Employee::firstWhere('name', $this->payload()['name']);
        $this->assertNotNull($employee);
        $this->assertSame(now()->format('ym').'01', $employee->employee_number);

        $this->actingAs($user)->put(route('hr.update', $employee), $this->payload(['name' => 'Siti Aminah', 'status' => 'on_leave']))
            ->assertRedirect(route('hr.index'));
        $this->assertSame('Siti Aminah', $employee->fresh()->name);
        $this->assertSame('on_leave', $employee->fresh()->status);

        $this->actingAs($user)->delete(route('hr.destroy', $employee))
            ->assertRedirect(route('hr.index'));
        $this->assertModelMissing($employee);
    }

    public function test_view_only_user_cannot_manage_employees(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['access-erp', 'hr.view']);
        $employee = Employee::factory()->create();

        $this->actingAs($user)->get(route('hr.index'))->assertOk();
        $this->actingAs($user)->get(route('hr.create'))->assertForbidden();
        $this->actingAs($user)->post(route('hr.store'), $this->payload())->assertForbidden();
        $this->actingAs($user)->delete(route('hr.destroy', $employee))->assertForbidden();
    }

    public function test_fields_are_validated(): void
    {
        $this->actingAs($this->userWithRole('Finance'))
            ->post(route('hr.store'), $this->payload(['employment_type' => 'bogus', 'email' => 'nope']))
            ->assertSessionHasErrors(['employment_type', 'email']);
    }

    public function test_employee_numbers_are_generated_and_never_repeat(): void
    {
        $prefix = now()->format('ym');
        Employee::factory()->create(['employee_number' => $prefix.'01']); // taken by hand: skipped

        $first = Employee::factory()->create(['employee_number' => null]);
        $second = Employee::factory()->create(['employee_number' => null]);
        $this->assertSame($prefix.'02', $first->employee_number);
        $this->assertSame($prefix.'03', $second->employee_number);

        $second->delete();
        $this->assertSame($prefix.'04', Employee::factory()->create(['employee_number' => null])->employee_number);
    }

    public function test_index_filters_by_status(): void
    {
        Employee::factory()->create(['name' => 'Karyawan Aktif', 'status' => 'active']);
        Employee::factory()->create(['name' => 'Karyawan Keluar', 'status' => 'resigned']);

        $this->actingAs($this->userWithRole('Finance'))
            ->get(route('hr.index', ['status' => 'resigned']))
            ->assertSee('Karyawan Keluar')
            ->assertDontSee('Karyawan Aktif');
    }

    public function test_a_signature_can_be_uploaded_replaced_and_removed(): void
    {
        $user = $this->userWithRole('Finance');

        $this->actingAs($user)->post(route('hr.store'), $this->payload([
            'signature' => UploadedFile::fake()->createWithContent('sig.png', $this->pngBytes()),
        ]))->assertRedirect(route('hr.index'));

        $employee = Employee::firstWhere('name', $this->payload()['name']);
        $this->assertNotNull($employee->signature_path);
        Storage::disk('public')->assertExists($employee->signature_path);
        $oldPath = $employee->signature_path;

        $this->actingAs($user)->put(route('hr.update', $employee), $this->payload([
            'signature' => UploadedFile::fake()->createWithContent('new-sig.png', $this->pngBytes()),
        ]))->assertRedirect(route('hr.index'));
        $employee->refresh();
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($employee->signature_path);
        $newPath = $employee->signature_path;

        // Saving without touching the file keeps the current signature.
        $this->actingAs($user)->put(route('hr.update', $employee), $this->payload());
        $this->assertSame($newPath, $employee->fresh()->signature_path);

        $this->actingAs($user)->put(route('hr.update', $employee), $this->payload(['remove_signature' => '1']));
        $this->assertNull($employee->fresh()->signature_path);
        Storage::disk('public')->assertMissing($newPath);
    }

    public function test_deleting_an_employee_deletes_their_signature_file(): void
    {
        $user = $this->userWithRole('Finance');
        $this->actingAs($user)->post(route('hr.store'), $this->payload([
            'signature' => UploadedFile::fake()->createWithContent('sig.png', $this->pngBytes()),
        ]));
        $employee = Employee::firstWhere('name', $this->payload()['name']);
        $path = $employee->signature_path;

        $this->actingAs($user)->delete(route('hr.destroy', $employee));

        Storage::disk('public')->assertMissing($path);
    }
}
