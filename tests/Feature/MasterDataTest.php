<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MasterDataItem;
use App\Models\User;
use App\Services\MasterData;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        return $user;
    }

    private function item(string $group, string $code): MasterDataItem
    {
        return MasterDataItem::where('group', $group)->where('code', $code)->firstOrFail();
    }

    public function test_defaults_are_seeded_by_migration_for_every_group(): void
    {
        foreach (array_keys(MasterData::GROUPS) as $group) {
            $this->assertNotEmpty(MasterData::options($group), $group);
        }

        $this->assertSame('Government / Institution', MasterData::label('customer_type', 'government'));
    }

    public function test_only_super_admin_can_open_master_data(): void
    {
        $this->actingAs($this->admin())->get(route('masterdata.index'))->assertOk()->assertSee('Customer Type');

        $finance = User::factory()->create();
        $finance->assignRole('Finance');
        $this->actingAs($finance)->get(route('masterdata.index'))->assertForbidden();
        $this->actingAs($finance)->post(route('masterdata.store'), ['group' => 'customer_type', 'label' => 'X'])->assertForbidden();
    }

    public function test_every_group_page_renders(): void
    {
        foreach (array_keys(MasterData::GROUPS) as $group) {
            $this->actingAs($this->admin())->get(route('masterdata.index', ['group' => $group]))->assertOk();
        }
    }

    public function test_new_option_shows_up_in_forms_and_is_accepted_by_validation(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('masterdata.store'), ['group' => 'customer_type', 'label' => 'Startup Baru'])
            ->assertRedirect(route('masterdata.index', ['group' => 'customer_type']));

        $item = MasterDataItem::firstWhere('label', 'Startup Baru');
        $this->assertSame('startup_baru', $item->code);

        $this->actingAs($admin)->get(route('sales.create'))->assertSee('Startup Baru');

        $this->actingAs($admin)->post(route('sales.store'), [
            'name' => 'PT Startup', 'phone' => '0812', 'customer_type' => 'startup_baru', 'is_active' => '1',
        ])->assertRedirect(route('sales.index'));
        $this->assertSame('startup_baru', Customer::firstWhere('name', 'PT Startup')->customer_type);
        $this->actingAs($admin)->get(route('sales.index'))->assertSee('Startup Baru');
    }

    public function test_unknown_option_is_rejected_by_validation(): void
    {
        $this->actingAs($this->admin())->post(route('sales.store'), [
            'name' => 'PT X', 'customer_type' => 'not_in_master_data', 'is_active' => '1',
        ])->assertSessionHasErrors('customer_type');
    }

    public function test_renaming_keeps_code_and_existing_records_show_new_label(): void
    {
        $customer = Customer::factory()->create(['customer_type' => 'company']);
        $item = $this->item('customer_type', 'company');

        $this->actingAs($this->admin())->put(route('masterdata.update', $item), ['label' => 'Perusahaan', 'sort_order' => 1, 'is_active' => '1'])
            ->assertRedirect();

        $this->assertSame('company', $item->fresh()->code);
        $this->assertSame('Perusahaan', MasterData::label('customer_type', $customer->customer_type));
    }

    public function test_deactivated_option_is_hidden_from_new_forms_but_still_labelled(): void
    {
        $item = $this->item('asset_category', 'vehicle');

        $this->actingAs($this->admin())->put(route('masterdata.update', $item), ['label' => 'Vehicle', 'sort_order' => 3, 'is_active' => '0']);

        $this->assertArrayNotHasKey('vehicle', MasterData::options('asset_category'));
        $this->assertArrayHasKey('vehicle', MasterData::options('asset_category', 'vehicle'));
        $this->assertSame('Vehicle', MasterData::label('asset_category', 'vehicle'));
    }

    public function test_option_in_use_cannot_be_deleted_but_unused_can(): void
    {
        Customer::factory()->create(['customer_type' => 'individual']);
        $admin = $this->admin();

        $this->actingAs($admin)->delete(route('masterdata.destroy', $this->item('customer_type', 'individual')))->assertSessionHas('error');
        $this->assertNotNull($this->item('customer_type', 'individual'));

        $this->actingAs($admin)->delete(route('masterdata.destroy', $this->item('customer_type', 'government')))->assertSessionHas('status');
        $this->assertNull(MasterDataItem::where('group', 'customer_type')->where('code', 'government')->first());
        $this->assertArrayNotHasKey('government', MasterData::options('customer_type'));
    }

    public function test_annual_leave_is_protected_but_can_be_renamed(): void
    {
        $annual = $this->item('leave_type', 'annual');
        $admin = $this->admin();

        $this->actingAs($admin)->delete(route('masterdata.destroy', $annual))->assertSessionHas('error');
        $this->actingAs($admin)->put(route('masterdata.update', $annual), ['label' => 'Cuti Tahunan', 'sort_order' => 1, 'is_active' => '0'])
            ->assertSessionHas('error');
        $this->assertTrue($annual->fresh()->is_active);

        $this->actingAs($admin)->put(route('masterdata.update', $annual), ['label' => 'Cuti Tahunan', 'sort_order' => 1, 'is_active' => '1']);
        $this->assertSame('Cuti Tahunan', $annual->fresh()->label);
    }

    public function test_duplicate_labels_and_unknown_groups_are_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('masterdata.store'), ['group' => 'customer_type', 'label' => ' company '])->assertSessionHasErrors('label');
        $this->actingAs($admin)->post(route('masterdata.store'), ['group' => 'bogus', 'label' => 'Foo'])->assertSessionHasErrors('group');
        $this->actingAs($admin)->put(route('masterdata.update', $this->item('customer_type', 'company')), ['label' => 'Individual', 'sort_order' => 1])
            ->assertSessionHasErrors('label');
    }

    public function test_same_label_gets_a_unique_code_and_new_items_go_last(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('masterdata.store'), ['group' => 'delivery_mode', 'label' => 'Kelas Khusus']);
        $this->actingAs($admin)->post(route('masterdata.store'), ['group' => 'delivery_mode', 'label' => 'Kelas-Khusus']);

        $this->assertNotNull($this->item('delivery_mode', 'kelas_khusus'));
        $this->assertNotNull($this->item('delivery_mode', 'kelas_khusus_2'));
        $this->assertSame(['On-site', 'Online', 'Hybrid', 'Kelas Khusus', 'Kelas-Khusus'], array_values(MasterData::options('delivery_mode')));
    }
}
