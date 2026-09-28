<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AssetsTest extends TestCase
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
    private function payload(array $overrides = []): array
    {
        return [
            'asset_code' => 'AST-001',
            'name' => 'Laptop Dell',
            'category' => 'electronics',
            'status' => 'active',
            'purchase_date' => '2026-01-15',
            'purchase_cost' => 15000000,
            ...$overrides,
        ];
    }

    public function test_finance_role_can_list_assets(): void
    {
        Asset::factory()->create(['name' => 'Projector X']);

        $this->actingAs($this->userWithRole('Finance'))
            ->get(route('assets.index'))
            ->assertOk()
            ->assertSee('Projector X');
    }

    public function test_staff_without_permission_cannot_view_assets(): void
    {
        $this->actingAs($this->userWithRole('Staff'))
            ->get(route('assets.index'))
            ->assertForbidden();
    }

    public function test_manager_can_create_update_and_delete_asset(): void
    {
        $user = $this->userWithRole('Finance');

        $this->actingAs($user)->post(route('assets.store'), $this->payload())
            ->assertRedirect(route('assets.index'));
        $asset = Asset::firstWhere('asset_code', 'AST-001');
        $this->assertNotNull($asset);

        $this->actingAs($user)->put(route('assets.update', $asset), $this->payload(['name' => 'Laptop HP', 'status' => 'in_repair']))
            ->assertRedirect(route('assets.index'));
        $this->assertSame('Laptop HP', $asset->fresh()->name);
        $this->assertSame('in_repair', $asset->fresh()->status);

        $this->actingAs($user)->delete(route('assets.destroy', $asset))
            ->assertRedirect(route('assets.index'));
        $this->assertModelMissing($asset);
    }

    public function test_view_only_user_cannot_manage_assets(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['access-erp', 'assets.view']);
        $asset = Asset::factory()->create();

        $this->actingAs($user)->get(route('assets.index'))->assertOk();
        $this->actingAs($user)->get(route('assets.create'))->assertForbidden();
        $this->actingAs($user)->post(route('assets.store'), $this->payload())->assertForbidden();
        $this->actingAs($user)->delete(route('assets.destroy', $asset))->assertForbidden();
    }

    public function test_asset_code_must_be_unique_and_fields_validated(): void
    {
        Asset::factory()->create(['asset_code' => 'AST-001']);

        $this->actingAs($this->userWithRole('Finance'))
            ->post(route('assets.store'), $this->payload(['category' => 'bogus']))
            ->assertSessionHasErrors(['asset_code', 'category']);
    }

    public function test_index_filters_by_status(): void
    {
        Asset::factory()->create(['name' => 'Working Item', 'status' => 'active']);
        Asset::factory()->create(['name' => 'Broken Item', 'status' => 'disposed']);

        $this->actingAs($this->userWithRole('Finance'))
            ->get(route('assets.index', ['status' => 'disposed']))
            ->assertSee('Broken Item')
            ->assertDontSee('Working Item');
    }
}
