<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ErpModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Finance pages not yet built (Accounts/Categories/Income/Expenses/Transfers/Transactions/Reports
     * became real CRUD in Phase 6-11 and are tested separately in their own test classes).
     */
    public static function financeStubRoutes(): array
    {
        return [
            ['finance.dashboard'],
        ];
    }

    /** All finance.* routes, regardless of whether they're stubs or real, for permission-gate coverage. */
    public static function allFinanceRoutes(): array
    {
        return [
            ['finance.dashboard'],
            ['finance.accounts'],
            ['finance.categories'],
            ['finance.income'],
            ['finance.expenses'],
            ['finance.transfers'],
            ['finance.transactions'],
            ['finance.reports.index'],
        ];
    }

    /**
     * Routes that require finance.view permission specifically.
     * These are read-only: Accounts (view only in list), Income, Expenses, Transfers, Transactions.
     */
    public static function financeViewOnlyRoutes(): array
    {
        return [
            ['finance.accounts'],
            ['finance.income'],
            ['finance.expenses'],
            ['finance.transfers'],
            ['finance.transactions'],
        ];
    }

    #[DataProvider('financeStubRoutes')]
    public function test_finance_role_can_view_finance_stub_pages(string $routeName): void
    {
        $user = User::factory()->create();
        $user->assignRole('Finance');

        $response = $this->actingAs($user)->get(route($routeName));

        $response->assertOk();
        // Assert the stub badge appears in the main content area, not just
        // incidentally in the sidebar's own "Coming Soon" module badges.
        $response->assertSeeInOrder(['max-w-3xl', 'Coming Soon'], false);
    }

    #[DataProvider('allFinanceRoutes')]
    public function test_staff_role_cannot_view_finance_pages(string $routeName): void
    {
        $user = User::factory()->create();
        $user->assignRole('Staff');

        $response = $this->actingAs($user)->get(route($routeName));

        $response->assertForbidden();
    }

    public static function comingSoonModuleRoutes(): array
    {
        return [
            ['sales.index'],
            ['training.index'],
            ['projects.index'],
            ['hr.index'],
            ['assets.index'],
        ];
    }

    #[DataProvider('comingSoonModuleRoutes')]
    public function test_any_erp_user_can_view_generic_coming_soon_modules(string $routeName): void
    {
        $user = User::factory()->create();
        $user->assignRole('Staff');

        $response = $this->actingAs($user)->get(route($routeName));

        $response->assertOk();
        $response->assertSee('Coming Soon');
    }

    public static function settingsRoutes(): array
    {
        return [
            ['settings.users'],
            ['settings.roles'],
            ['settings.system'],
        ];
    }

    #[DataProvider('settingsRoutes')]
    public function test_non_admin_roles_cannot_view_settings_pages(string $routeName): void
    {
        foreach (['Finance', 'Staff', 'Viewer'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);

            $response = $this->actingAs($user)->get(route($routeName));

            $response->assertForbidden();
        }
    }

    #[DataProvider('settingsRoutes')]
    public function test_super_admin_can_view_settings_pages(string $routeName): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $response = $this->actingAs($user)->get(route($routeName));

        $response->assertOk();
    }

    public function test_sidebar_hides_finance_group_for_staff_without_finance_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Staff');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('Accounts');
        $response->assertDontSee('Categories');
    }

    public function test_sidebar_shows_finance_group_for_finance_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Finance');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Finance');
        $response->assertSee('Accounts');
    }

    public function test_sidebar_hides_settings_group_for_non_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Viewer');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('System Settings');
    }

    public function test_sidebar_shows_settings_group_for_super_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('System Settings');
    }
}
