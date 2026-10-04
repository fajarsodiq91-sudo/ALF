<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IdCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs(User::factory()->create()->assignRole('Super Admin'));
    }

    public function test_employee_id_card_shows_role_number_name_and_qr(): void
    {
        $employee = Employee::factory()->create();

        $this->get(route('hr.id-card', $employee))
            ->assertOk()
            ->assertSee($employee->position)
            ->assertSee('PT Alfajar Logic Futura')
            ->assertSee($employee->employee_number)
            ->assertSee($employee->name)
            ->assertSee($employee->position)
            ->assertSee('<svg', false);
    }

    public function test_customer_card_shows_the_customer_since_date(): void
    {
        $customer = Customer::factory()->create(['approved_at' => '2026-03-15 10:00:00']);

        $this->get(route('sales.id-card', $customer))->assertSee('Valid from 15 Maret 2026');
    }

    public function test_qr_scan_page_verifies_an_active_employee_and_flags_a_resigned_one(): void
    {
        $active = Employee::factory()->create(['status' => 'active']);
        $resigned = Employee::factory()->create(['status' => 'resigned']);

        auth()->logout();

        $this->get(route('id-cards.verify', $active->idCardToken()))
            ->assertOk()->assertSee('ID Card valid')->assertSee($active->name)->assertSee($active->position);
        $this->get(route('id-cards.verify', $resigned->idCardToken()))
            ->assertOk()->assertSee('ID Card tidak berlaku');
    }

    public function test_qr_scan_page_flags_an_inactive_customer_and_rejects_unknown_tokens(): void
    {
        $inactive = Customer::factory()->create(['is_active' => false]);

        auth()->logout();

        $this->get(route('id-cards.verify', $inactive->idCardToken()))->assertSee('ID Card tidak berlaku');
        $this->get(route('id-cards.verify', 'unknown'))->assertNotFound();
    }

    public function test_bulk_print_respects_the_list_filters(): void
    {
        $kept = Employee::factory()->create(['name' => 'Budi Santoso']);
        $other = Employee::factory()->create(['name' => 'Citra Lestari']);

        $this->get(route('hr.id-cards', ['q' => 'Budi']))
            ->assertOk()->assertSee($kept->employee_number)->assertDontSee($other->employee_number);
    }

    public function test_customer_id_card_shows_role_and_code(): void
    {
        $customer = Customer::factory()->create();

        $this->get(route('sales.id-card', $customer))
            ->assertOk()
            ->assertSee('Customer')
            ->assertSee($customer->customer_code);
    }

    public function test_customer_without_a_permanent_id_has_no_card(): void
    {
        $customer = Customer::factory()->create(['registration_status' => Customer::REGISTRATION_AWAITING]);

        $this->get(route('sales.id-card', $customer))->assertNotFound();
    }

    public function test_customer_opens_their_own_id_card_from_the_portal(): void
    {
        $customer = Customer::factory()->create(['must_change_password' => false]);
        $other = Customer::factory()->create();

        auth()->logout();

        $this->actingAs($customer, 'customer')->get(route('portal.dashboard'))
            ->assertOk()->assertSee(route('portal.id-card'), false);
        $this->actingAs($customer, 'customer')->get(route('portal.id-card'))
            ->assertOk()->assertSee($customer->customer_code)->assertDontSee($other->customer_code);
    }

    public function test_rfid_uid_is_recorded_in_a_normalized_form(): void
    {
        $employee = Employee::factory()->create();

        $this->put(route('hr.update', $employee), $this->employeeData($employee, ['rfid_uid' => '04:a1 b2-c3']))->assertSessionHasNoErrors();

        $this->assertSame('04A1B2C3', $employee->fresh()->rfid_uid);
    }

    public function test_a_card_cannot_be_registered_to_two_people(): void
    {
        $employee = Employee::factory()->create(['rfid_uid' => '04A1B2C3']);
        $other = Employee::factory()->create();
        $customer = Customer::factory()->create();

        $this->put(route('hr.update', $other), $this->employeeData($other, ['rfid_uid' => '04a1b2c3']))->assertSessionHasErrors('rfid_uid');
        $this->put(route('sales.update', $customer), [
            'name' => $customer->name, 'customer_type' => $customer->customer_type, 'phone' => $customer->phone, 'rfid_uid' => '04A1B2C3',
        ])->assertSessionHasErrors('rfid_uid');

        // Saving the owner again with their own card is fine.
        $this->put(route('hr.update', $employee), $this->employeeData($employee, ['rfid_uid' => '04A1B2C3']))->assertSessionHasNoErrors();
    }

    /** @param array<string, mixed> $overrides */
    private function employeeData(Employee $employee, array $overrides = []): array
    {
        return [
            'name' => $employee->name,
            'position' => $employee->position,
            'employment_type' => $employee->employment_type,
            'status' => $employee->status,
            'annual_leave_quota' => $employee->annual_leave_quota,
            ...$overrides,
        ];
    }
}
