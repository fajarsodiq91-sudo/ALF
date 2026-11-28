<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EmployeeLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_an_active_employee_gets_a_staff_login_on_their_employee_number(): void
    {
        $employee = Employee::factory()->create(['email' => 'siti@example.com', 'status' => 'active']);

        $user = $employee->user;
        $this->assertNotNull($user);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->hasRole('Staff'));
        $this->assertSame('siti@example.com', $user->email);
        $this->assertTrue(Hash::check($employee->employee_number, $user->password));
    }

    public function test_an_employee_without_email_still_gets_a_login(): void
    {
        $employee = Employee::factory()->create(['email' => null]);

        $this->assertNull($employee->user->email);
    }

    public function test_resigned_employees_get_no_login_but_employees_on_leave_do(): void
    {
        $resigned = Employee::factory()->create(['status' => 'resigned']);
        $onLeave = Employee::factory()->create(['status' => 'on_leave']);

        $this->assertNull($resigned->user);
        $this->assertTrue($onLeave->user->is_active);
    }

    public function test_going_on_leave_keeps_the_login_active(): void
    {
        $employee = Employee::factory()->create();

        $employee->update(['status' => 'on_leave']);

        $this->assertTrue($employee->user->fresh()->is_active);
    }

    public function test_an_employee_logs_in_with_their_employee_number_and_must_change_the_password_first(): void
    {
        $employee = Employee::factory()->create(['email' => null]);

        $this->post('/login', ['email' => $employee->employee_number, 'password' => $employee->employee_number])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($employee->user);

        $this->get(route('dashboard'))->assertRedirect(route('profile.edit'));
        $this->get(route('profile.edit'))->assertOk()
            ->assertSee('Please set a new password')
            ->assertSee('Set your new password')
            ->assertSee($employee->employee_number)
            ->assertDontSee('Delete Account');

        $this->put(route('password.update'), [
            'current_password' => $employee->employee_number,
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));

        $this->assertFalse($employee->user->fresh()->must_change_password);
        $this->get(route('dashboard'))->assertOk()->assertSee('Your new password is set. Welcome to the ERP!');
        $this->get(route('dashboard'))->assertOk()->assertDontSee('Your new password is set');
        $this->get(route('profile.edit'))->assertOk()->assertDontSee('Set your new password');
    }

    public function test_a_wrong_current_password_is_shown_on_the_first_login_page(): void
    {
        $employee = Employee::factory()->create(['email' => null]);
        $this->actingAs($employee->user);

        $this->from(route('profile.edit'))->followingRedirects()->put(route('password.update'), [
            'current_password' => 'not-it',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])->assertOk()->assertSee('Set your new password')->assertSee('role="alert"', false);

        $this->assertTrue($employee->user->fresh()->must_change_password);
    }

    public function test_an_unknown_employee_number_cannot_log_in(): void
    {
        User::factory()->create(); // a non-employee account must never match a missing employee

        $this->post('/login', ['email' => 'NOPE-123', 'password' => 'password']);

        $this->assertGuest();
    }

    public function test_changing_the_status_away_from_active_deactivates_the_login_and_back_reactivates_it(): void
    {
        $employee = Employee::factory()->create();

        $employee->update(['status' => 'resigned']);
        $this->assertFalse($employee->user->fresh()->is_active);

        $this->post('/login', ['email' => $employee->employee_number, 'password' => $employee->employee_number]);
        $this->assertGuest();

        $employee->update(['status' => 'active']);
        $this->assertTrue($employee->user->fresh()->is_active);
    }

    public function test_an_unrelated_edit_does_not_reopen_a_login_an_admin_switched_off(): void
    {
        $employee = Employee::factory()->create();
        $employee->user->update(['is_active' => false]);

        $employee->update(['phone' => '0812345678']);

        $this->assertFalse($employee->user->fresh()->is_active);
    }

    public function test_deleting_an_employee_deactivates_the_login(): void
    {
        $employee = Employee::factory()->create();
        $user = $employee->user;

        $employee->delete();

        $user->refresh();
        $this->assertFalse($user->is_active);
        $this->assertNull($user->employee_id);
    }

    public function test_an_existing_account_with_the_same_email_is_linked_instead_of_duplicated(): void
    {
        $existing = User::factory()->create(['email' => 'andi@example.com']);
        $existing->assignRole('Finance');

        $employee = Employee::factory()->create(['email' => 'Andi@example.com']);

        $this->assertTrue($employee->user->is($existing));
        $this->assertFalse($existing->fresh()->must_change_password);
        $this->assertTrue($existing->fresh()->hasRole('Finance'));
        $this->assertSame(1, User::where('email', 'andi@example.com')->count());
    }

    public function test_hr_creating_an_active_employee_creates_the_login(): void
    {
        $hr = User::factory()->create();
        $hr->assignRole('Finance');

        $this->actingAs($hr)->post(route('hr.store'), [
            'name' => 'Budi Santoso',
            'position' => 'Developer',
            'employment_type' => 'permanent',
            'status' => 'active',
            'annual_leave_quota' => 12,
        ])->assertRedirect(route('hr.index'));

        $this->assertNotNull(Employee::where('name', 'Budi Santoso')->first()->user);
    }
}
