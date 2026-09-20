<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_super_admin_can_create_user_who_can_then_log_in(): void
    {
        $this->actingAs($this->user('Super Admin'))->post(route('settings.users.store'), [
            'name' => 'New Finance', 'email' => 'new@example.com', 'password' => 'Str0ng-Pass!word', 'role' => 'Finance', 'is_active' => 1,
        ])->assertRedirect(route('settings.users'));

        $created = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertTrue($created->hasRole('Finance'));
        $this->assertNotNull($created->email_verified_at);

        auth()->logout();
        $this->post('/login', ['email' => 'new@example.com', 'password' => 'Str0ng-Pass!word'])->assertRedirect();
        $this->assertAuthenticatedAs($created);
    }

    public function test_deactivated_user_cannot_log_in_and_is_kicked_from_active_session(): void
    {
        $target = $this->user('Finance');
        $target->update(['is_active' => false]);

        $this->post('/login', ['email' => $target->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($target)->get(route('finance.dashboard'))->assertRedirect(route('login'));
    }

    public function test_user_update_can_change_role_and_optionally_password(): void
    {
        $admin = $this->user('Super Admin');
        $target = $this->user('Staff');
        $oldHash = $target->password;

        $this->actingAs($admin)->put(route('settings.users.update', $target), [
            'name' => 'Renamed', 'email' => $target->email, 'password' => '', 'role' => 'Viewer', 'is_active' => 1,
        ])->assertRedirect(route('settings.users'));

        $target->refresh();
        $this->assertSame('Renamed', $target->name);
        $this->assertTrue($target->hasRole('Viewer'));
        $this->assertFalse($target->hasRole('Staff'));
        $this->assertSame($oldHash, $target->password);
    }

    public function test_cannot_deactivate_self_or_demote_last_super_admin(): void
    {
        $admin = $this->user('Super Admin');

        $this->actingAs($admin)->put(route('settings.users.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'Super Admin', 'is_active' => 0,
        ])->assertSessionHas('error');

        $this->actingAs($admin)->put(route('settings.users.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'Viewer', 'is_active' => 1,
        ])->assertSessionHas('error');

        $this->assertTrue($admin->fresh()->hasRole('Super Admin'));
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_custom_role_lifecycle_and_built_in_protection(): void
    {
        $admin = $this->user('Super Admin');

        $this->actingAs($admin)->post(route('settings.roles.store'), [
            'name' => 'Auditor', 'permissions' => ['access-erp', 'finance.view'],
        ])->assertRedirect(route('settings.roles'));
        $role = Role::findByName('Auditor');
        $this->assertTrue($role->hasPermissionTo('finance.view'));

        $this->actingAs($admin)->put(route('settings.roles.update', $role), [
            'name' => 'Auditor 2', 'permissions' => ['access-erp'],
        ])->assertRedirect(route('settings.roles'));
        $this->assertFalse($role->fresh()->hasPermissionTo('finance.view'));

        $holder = $this->user('Auditor 2');
        $this->actingAs($admin)->delete(route('settings.roles.destroy', $role))->assertSessionHas('error');
        $holder->removeRole('Auditor 2');
        $this->actingAs($admin)->delete(route('settings.roles.destroy', $role->fresh()))->assertSessionHas('status');
        $this->assertNull(Role::where('name', 'Auditor 2')->first());

        $finance = Role::findByName('Finance');
        $this->actingAs($admin)->delete(route('settings.roles.destroy', $finance))->assertSessionHas('error');
        $this->actingAs($admin)->put(route('settings.roles.update', $finance), [
            'name' => 'Hacked', 'permissions' => ['access-erp'],
        ])->assertSessionHasErrors('name');
        $this->actingAs($admin)->get(route('settings.roles.edit', Role::findByName('Super Admin')))->assertRedirect(route('settings.roles'));
    }

    public function test_built_in_role_permissions_are_editable_and_survive_reseeding(): void
    {
        $admin = $this->user('Super Admin');
        $viewer = Role::findByName('Viewer');

        $this->actingAs($admin)->put(route('settings.roles.update', $viewer), [
            'permissions' => ['access-erp'],
        ])->assertRedirect(route('settings.roles'));
        $this->assertFalse($viewer->fresh()->hasPermissionTo('finance.view'));

        $this->seed(RolePermissionSeeder::class);
        $this->assertFalse(Role::findByName('Viewer')->hasPermissionTo('finance.view'));
    }

    public function test_system_settings_are_saved_and_used_in_layout(): void
    {
        $admin = $this->user('Super Admin');

        $this->actingAs($admin)->put(route('settings.system.update'), [
            'company_name' => 'PT Contoh Baru', 'company_email' => 'info@contoh.test', 'company_npwp' => '01.234.567.8-901.000',
        ])->assertRedirect(route('settings.system'));

        $this->assertSame('PT Contoh Baru', Setting::get('company_name'));
        $this->actingAs($admin)->get(route('settings.system'))->assertOk()->assertSee('PT Contoh Baru');
        $this->actingAs($admin)->get(route('dashboard'))->assertSee('PT Contoh Baru', false);
    }

    public function test_non_admins_cannot_use_any_settings_endpoint(): void
    {
        $finance = $this->user('Finance');

        $this->actingAs($finance)->post(route('settings.users.store'), [])->assertForbidden();
        $this->actingAs($finance)->post(route('settings.roles.store'), [])->assertForbidden();
        $this->actingAs($finance)->put(route('settings.system.update'), [])->assertForbidden();
    }

    public function test_create_admin_command_creates_super_admin(): void
    {
        $this->artisan('erp:create-admin', ['email' => 'owner@example.com', '--name' => 'Owner'])
            ->expectsQuestion('Password (min 8 characters)', 'Sup3r-Secret!')
            ->assertSuccessful();

        $user = User::where('email', 'owner@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('Super Admin'));
        $this->assertNotNull($user->email_verified_at);
    }
}
