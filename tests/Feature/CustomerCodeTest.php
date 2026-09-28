<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Services\CustomerCodeGenerator;
use Database\Seeders\RolePermissionSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
            'name' => 'PT Baru', 'customer_type' => 'company', 'is_active' => '1', 'customer_code' => '999999',
        ])->assertRedirect(route('sales.index'));

        $this->assertSame('260901', Customer::firstWhere('name', 'PT Baru')->customer_code);
    }

    public function test_full_month_shows_error_instead_of_crashing(): void
    {
        for ($i = 0; $i < CustomerCodeGenerator::MAX_PER_MONTH; $i++) {
            CustomerCodeGenerator::next(now());
        }

        $this->actingAs($this->financeUser())->post(route('sales.store'), [
            'name' => 'PT Penuh', 'customer_type' => 'company',
        ])->assertSessionHas('error');
        $this->assertNull(Customer::firstWhere('name', 'PT Penuh'));
    }

    public function test_code_cannot_be_changed_by_update(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->financeUser())->put(route('sales.update', $customer), [
            'name' => 'Nama Baru', 'customer_type' => 'company', 'is_active' => '1', 'customer_code' => '000000',
        ])->assertRedirect(route('sales.index'));

        $this->assertSame('260901', $customer->fresh()->customer_code);
        $this->assertSame('Nama Baru', $customer->fresh()->name);
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
