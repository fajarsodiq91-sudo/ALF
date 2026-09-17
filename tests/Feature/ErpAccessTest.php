<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ErpAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/erp/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_user_without_access_erp_permission_is_forbidden(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/erp/dashboard');

        $response->assertForbidden();
    }

    public function test_staff_role_can_access_erp_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Staff');

        $response = $this->actingAs($user)->get('/erp/dashboard');

        $response->assertOk();
        $response->assertSee('ERP Dashboard');
    }

    public function test_finance_role_can_access_erp_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Finance');

        $response = $this->actingAs($user)->get('/erp/dashboard');

        $response->assertOk();
    }

    public function test_super_admin_bypasses_permission_checks_without_explicit_grant(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        // Super Admin should reach a permission-gated route even for a
        // permission that doesn't exist yet, via the Gate::before bypass.
        $this->assertTrue($user->can('some-future-module.manage'));

        $response = $this->actingAs($user)->get('/erp/dashboard');

        $response->assertOk();
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('Viewer');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/erp/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }
}
