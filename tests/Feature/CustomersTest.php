<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomersTest extends TestCase
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
            'name' => 'PT Maju Jaya',
            'customer_type' => 'company',
            'phone' => '081234567890',
            'email' => 'budi@majujaya.test',
            'is_active' => '1',
            ...$overrides,
        ];
    }

    public function test_finance_role_can_list_customers(): void
    {
        Customer::factory()->create(['name' => 'PT Contoh Sejahtera']);

        $this->actingAs($this->userWithRole('Finance'))
            ->get(route('sales.index'))
            ->assertOk()
            ->assertSee('PT Contoh Sejahtera');
    }

    public function test_staff_without_permission_cannot_view_sales(): void
    {
        $this->actingAs($this->userWithRole('Staff'))
            ->get(route('sales.index'))
            ->assertForbidden();
    }

    public function test_manager_can_create_update_and_delete_customer(): void
    {
        $user = $this->userWithRole('Finance');

        $this->actingAs($user)->post(route('sales.store'), $this->payload())
            ->assertRedirect(route('sales.index'));
        $customer = Customer::firstWhere('name', 'PT Maju Jaya');
        $this->assertNotNull($customer);

        $this->actingAs($user)->put(route('sales.update', $customer), $this->payload(['name' => 'PT Maju Terus', 'is_active' => '0']))
            ->assertRedirect(route('sales.index'));
        $this->assertSame('PT Maju Terus', $customer->fresh()->name);
        $this->assertFalse($customer->fresh()->is_active);

        $this->actingAs($user)->delete(route('sales.destroy', $customer))
            ->assertRedirect(route('sales.index'));
        $this->assertModelMissing($customer);
    }

    public function test_view_only_user_cannot_manage_customers(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['access-erp', 'sales.view']);
        $customer = Customer::factory()->create();

        $this->actingAs($user)->get(route('sales.index'))->assertOk();
        $this->actingAs($user)->get(route('sales.create'))->assertForbidden();
        $this->actingAs($user)->post(route('sales.store'), $this->payload())->assertForbidden();
        $this->actingAs($user)->delete(route('sales.destroy', $customer))->assertForbidden();
    }

    public function test_customer_fields_are_validated(): void
    {
        $this->actingAs($this->userWithRole('Finance'))
            ->post(route('sales.store'), $this->payload(['name' => '', 'customer_type' => 'bogus', 'email' => 'not-an-email']))
            ->assertSessionHasErrors(['name', 'customer_type', 'email']);
    }

    public function test_index_filters_by_status(): void
    {
        Customer::factory()->create(['name' => 'Pelanggan Aktif', 'is_active' => true]);
        Customer::factory()->create(['name' => 'Pelanggan Lama', 'is_active' => false]);

        $this->actingAs($this->userWithRole('Finance'))
            ->get(route('sales.index', ['status' => 'inactive']))
            ->assertSee('Pelanggan Lama')
            ->assertDontSee('Pelanggan Aktif');
    }

    public function test_create_and_edit_forms_render(): void
    {
        $user = $this->userWithRole('Finance');
        $customer = Customer::factory()->create();

        $this->actingAs($user)->get(route('sales.create'))->assertOk()->assertSee('Create Customer');
        $this->actingAs($user)->get(route('sales.edit', $customer))->assertOk()->assertSee('Update Customer');
    }
}
