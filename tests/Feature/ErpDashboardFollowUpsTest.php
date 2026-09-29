<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ErpDashboardFollowUpsTest extends TestCase
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

    public function test_the_follow_up_container_is_hidden_when_nothing_is_pending(): void
    {
        $staff = $this->userWithRole('Staff');

        $this->actingAs($staff)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Needs your attention');
    }

    public function test_the_follow_up_container_is_hidden_for_finance_with_nothing_pending(): void
    {
        $finance = $this->userWithRole('Finance');

        $this->actingAs($finance)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Needs your attention');
    }

    public function test_the_follow_up_container_shows_up_once_something_needs_attention(): void
    {
        Customer::factory()->pendingApproval()->create();
        $finance = $this->userWithRole('Finance');

        $this->actingAs($finance)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Needs your attention')
            ->assertSee('Customer registrations');
    }
}
