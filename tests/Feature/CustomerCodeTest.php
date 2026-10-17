<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Services\CustomerCodeGenerator;
use Database\Seeders\RolePermissionSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomerCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Carbon::setTestNow('2026-09-15 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function financeUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Finance');

        return $user;
    }

    public function test_code_is_year_month_and_running_number(): void
    {
        $this->assertSame('260901', Customer::factory()->create()->customer_code);
        $this->assertSame('260902', Customer::factory()->create()->customer_code);
    }

    public function test_numbering_restarts_each_month(): void
    {
        Customer::factory()->count(2)->create();

        Carbon::setTestNow('2026-10-01 08:00:00');
        $this->assertSame('261001', Customer::factory()->create()->customer_code);

        Carbon::setTestNow('2027-01-05 08:00:00');
        $this->assertSame('270101', Customer::factory()->create()->customer_code);
    }

    public function test_deleted_customer_number_is_never_reused(): void
    {
        $first = Customer::factory()->create();
        $second = Customer::factory()->create();
        $second->delete();

        $this->assertSame('260901', $first->customer_code);
        $this->assertSame('260903', Customer::factory()->create()->customer_code);
    }

    public function test_month_is_capped_at_99_customers(): void
    {
        for ($i = 0; $i < CustomerCodeGenerator::MAX_PER_MONTH; $i++) {
            CustomerCodeGenerator::next(now());
        }

        $this->expectException(DomainException::class);
        CustomerCodeGenerator::next(now());
    }

    public function test_creating_through_the_form_assigns_code_and_ignores_submitted_code(): void
    {
        $this->actingAs($this->financeUser())->post(route('sales.store'), [
            'name' => 'PT Baru', 'phone' => '0812', 'customer_type' => 'company', 'is_active' => '1', 'customer_code' => '999999',
        ])->assertRedirect(route('sales.index'));

        $this->assertSame('260901', Customer::firstWhere('name', 'PT Baru')->customer_code);
    }

    public function test_full_month_shows_error_instead_of_crashing(): void
    {
        for ($i = 0; $i < CustomerCodeGenerator::MAX_PER_MONTH; $i++) {
            CustomerCodeGenerator::next(now());
        }

        $this->actingAs($this->financeUser())->post(route('sales.store'), [
            'name' => 'PT Penuh', 'phone' => '0812', 'customer_type' => 'company',
        ])->assertSessionHas('error');
        $this->assertNull(Customer::firstWhere('name', 'PT Penuh'));
    }

    private function update(Customer $customer, array $overrides = [])
    {
        return $this->actingAs($this->financeUser())->put(route('sales.update', $customer), [
            'name' => 'Nama Baru', 'phone' => '0812', 'customer_type' => 'company', 'is_active' => '1', ...$overrides,
        ]);
    }

    public function test_code_can_be_changed_to_match_the_month_the_customer_registered(): void
    {
        $customer = Customer::factory()->create()->refresh();
        $customer->resetPortalPassword();
        $this->assertSame('260901', $customer->customer_code);

        $this->update($customer, ['customer_code' => '260805'])->assertRedirect(route('sales.index'));

        $customer->refresh();
        $this->assertSame('260805', $customer->customer_code);
        $this->assertSame('Nama Baru', $customer->name);
        // The password had never been changed, so it follows the new ID.
        $this->assertTrue(Hash::check('260805', $customer->password));
        $this->assertTrue($customer->must_change_password);
    }

    public function test_a_password_the_customer_chose_is_kept_when_the_code_changes(): void
    {
        $customer = Customer::factory()->create();
        $customer->forceFill(['password' => 'rahasia-pilihan', 'must_change_password' => false])->save();

        $this->update($customer, ['customer_code' => '260805'])->assertRedirect(route('sales.index'));

        $this->assertTrue(Hash::check('rahasia-pilihan', $customer->fresh()->password));
    }

    public function test_code_must_be_a_valid_unused_yymmnn(): void
    {
        $customer = Customer::factory()->create();
        $other = Customer::factory()->create();

        foreach (['000000', '261301', '2608', 'abcdef', $other->customer_code] as $bad) {
            $this->update($customer, ['customer_code' => $bad])->assertSessionHasErrors('customer_code');
        }

        $this->assertSame('260901', $customer->fresh()->customer_code);
    }

    public function test_a_code_taken_by_a_deleted_customer_cannot_be_reused(): void
    {
        $customer = Customer::factory()->create();
        $gone = Customer::factory()->create();
        $gone->delete();

        $this->update($customer, ['customer_code' => $gone->customer_code])->assertSessionHasErrors('customer_code');
    }

    public function test_a_hand_picked_code_is_never_handed_out_again_by_the_counter(): void
    {
        $customer = Customer::factory()->create();

        $this->update($customer, ['customer_code' => '260950'])->assertRedirect(route('sales.index'));

        $this->assertSame('260951', Customer::factory()->create()->customer_code);
    }

    public function test_the_code_of_a_self_registered_or_participant_customer_cannot_be_changed(): void
    {
        $approved = Customer::factory()->withPortalAccess()->create();
        $participant = Customer::factory()->create(['company_customer_id' => Customer::factory()->create()->id]);

        foreach ([$approved, $participant] as $customer) {
            $original = $customer->customer_code;

            $this->assertFalse($customer->isManuallyAdded());
            $this->update($customer, ['customer_code' => '260805'])->assertRedirect(route('sales.index'));

            $this->assertSame($original, $customer->fresh()->customer_code);
            $this->assertSame('Nama Baru', $customer->fresh()->name);
            $this->actingAs($this->financeUser())->get(route('sales.edit', $customer))->assertOk()->assertDontSee('name="customer_code"', false);
        }
    }

    public function test_the_edit_form_offers_the_code_field_only_for_manually_added_customers(): void
    {
        $manual = Customer::factory()->create()->refresh();

        $this->assertTrue($manual->isManuallyAdded());
        $this->actingAs($this->financeUser())->get(route('sales.edit', $manual))->assertOk()->assertSee('name="customer_code"', false);
    }

    public function test_view_only_user_cannot_change_the_code(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['access-erp', 'sales.view']);
        $customer = Customer::factory()->create();

        $this->actingAs($viewer)->put(route('sales.update', $customer), ['name' => 'X', 'phone' => '1', 'customer_type' => 'company', 'customer_code' => '260805'])->assertForbidden();
        $this->assertSame('260901', $customer->fresh()->customer_code);
    }

    public function test_code_is_shown_and_searchable(): void
    {
        $customer = Customer::factory()->create(['name' => 'PT Terlihat']);
        Customer::factory()->create(['name' => 'PT Lain']);
        $finance = $this->financeUser();

        $this->actingAs($finance)->get(route('sales.index'))->assertSee($customer->customer_code);
        $this->actingAs($finance)->get(route('sales.index', ['q' => '260901']))->assertSee('PT Terlihat')->assertDontSee('PT Lain');
        $this->actingAs($finance)->get(route('sales.edit', $customer))->assertOk()->assertSee('260901');
    }
}
