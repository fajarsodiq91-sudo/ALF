<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Employee;
use App\Models\ExpenseTransaction;
use App\Models\Payroll;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HrPayrollTest extends TestCase
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
    private function payload(Employee $employee, array $overrides = []): array
    {
        return [
            'employee_id' => $employee->id,
            'period' => '2026-03',
            'basic_salary' => 8000000,
            'allowances' => 1000000,
            'deductions' => 500000,
            ...$overrides,
        ];
    }

    public function test_pages_render_for_finance_and_are_blocked_for_others(): void
    {
        $finance = $this->userWithRole('Finance');
        $payroll = Payroll::factory()->create(['period' => '2026-03']);

        $this->actingAs($finance)->get(route('hr.payroll.index', ['period' => '2026-03']))->assertOk()->assertSee($payroll->employee->name);
        $this->actingAs($finance)->get(route('hr.payroll.create'))->assertOk();
        $this->actingAs($finance)->get(route('hr.payroll.edit', $payroll))->assertOk();
        $this->actingAs($finance)->get(route('hr.payroll.show', $payroll))->assertOk()->assertSee('Net Salary');

        $hr = User::factory()->create();
        $hr->givePermissionTo(['access-erp', 'hr.view', 'hr.manage']);
        $this->actingAs($hr)->get(route('hr.payroll.index'))->assertForbidden();
        $this->actingAs($this->userWithRole('Staff'))->get(route('hr.payroll.index'))->assertForbidden();
    }

    public function test_net_salary_is_calculated_and_period_is_unique(): void
    {
        $finance = $this->userWithRole('Finance');
        $employee = Employee::factory()->create();

        $this->actingAs($finance)->post(route('hr.payroll.store'), $this->payload($employee))->assertRedirect();
        $this->assertSame('8500000.00', Payroll::first()->net_salary);

        $this->actingAs($finance)->post(route('hr.payroll.store'), $this->payload($employee))->assertSessionHasErrors('period');
        $this->actingAs($finance)->post(route('hr.payroll.store'), $this->payload($employee, ['period' => '2026-04', 'deductions' => 99000000]))->assertSessionHasErrors('deductions');
    }

    public function test_paying_creates_finance_expense_and_locks_payroll(): void
    {
        $finance = $this->userWithRole('Finance');
        $account = Account::factory()->create();
        $payroll = Payroll::factory()->create(['period' => '2026-03', 'net_salary' => 8500000]);

        $this->actingAs($finance)->post(route('hr.payroll.pay', $payroll), [
            'account_id' => $account->id, 'paid_date' => '2026-03-28', 'payment_method' => 'Bank Transfer',
        ])->assertRedirect();

        $payroll->refresh();
        $this->assertTrue($payroll->isPaid());
        $expense = ExpenseTransaction::findOrFail($payroll->expense_transaction_id);
        $this->assertSame('8500000.00', $expense->amount);
        $this->assertSame($account->id, $expense->account_id);
        $this->assertSame(Payroll::SALARY_CATEGORY, $expense->category->name);

        $this->actingAs($finance)->get(route('hr.payroll.edit', $payroll))->assertRedirect()->assertSessionHas('error');
        $this->actingAs($finance)->delete(route('hr.payroll.destroy', $payroll))->assertSessionHas('error');
        $this->assertModelExists($payroll);
        $this->actingAs($finance)->post(route('hr.payroll.pay', $payroll), [
            'account_id' => $account->id, 'paid_date' => '2026-03-28', 'payment_method' => 'Cash',
        ])->assertSessionHas('error');
        $this->assertSame(1, ExpenseTransaction::count());
    }

    public function test_cancel_payment_removes_expense(): void
    {
        $finance = $this->userWithRole('Finance');
        $account = Account::factory()->create();
        $payroll = Payroll::factory()->create();

        $this->actingAs($finance)->post(route('hr.payroll.pay', $payroll), [
            'account_id' => $account->id, 'paid_date' => '2026-03-28', 'payment_method' => 'Cash',
        ]);
        $this->actingAs($finance)->post(route('hr.payroll.cancel-payment', $payroll->fresh()))->assertRedirect();

        $this->assertSame(Payroll::DRAFT, $payroll->fresh()->status);
        $this->assertSame(0, ExpenseTransaction::count());
    }

    public function test_payroll_permission_without_finance_manage_cannot_pay(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['access-erp', 'hr.payroll']);
        $account = Account::factory()->create();
        $payroll = Payroll::factory()->create();

        $this->actingAs($user)->post(route('hr.payroll.pay', $payroll), [
            'account_id' => $account->id, 'paid_date' => '2026-03-28', 'payment_method' => 'Cash',
        ])->assertForbidden();
    }

    public function test_index_paginates_and_the_total_covers_every_page(): void
    {
        $finance = $this->userWithRole('Finance');

        foreach (range(1, 25) as $i) {
            $employee = Employee::factory()->create(['name' => 'Employee '.str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
            Payroll::factory()->create(['employee_id' => $employee->id, 'period' => '2026-03', 'net_salary' => 1000]);
        }

        $response = $this->actingAs($finance)->get(route('hr.payroll.index', ['period' => '2026-03']));

        $response->assertOk()
            ->assertSee('Employee 01')
            ->assertDontSee('Employee 25')
            ->assertSee('Rp 25.000'); // total net across all 25, not just the page shown
    }

    public function test_index_sorts_by_employee_name_across_pages(): void
    {
        $finance = $this->userWithRole('Finance');
        $zed = Employee::factory()->create(['name' => 'Zed Zulkarnain']);
        $anna = Employee::factory()->create(['name' => 'Anna Aditya']);
        Payroll::factory()->create(['employee_id' => $zed->id, 'period' => '2026-03']);
        Payroll::factory()->create(['employee_id' => $anna->id, 'period' => '2026-03']);

        $response = $this->actingAs($finance)->get(route('hr.payroll.index', ['period' => '2026-03']));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertLessThan(strpos($content, 'Zed Zulkarnain'), strpos($content, 'Anna Aditya'));
    }

    public function test_filtering_by_status_without_a_period_does_not_load_every_payroll_unbounded(): void
    {
        $finance = $this->userWithRole('Finance');

        foreach (range(1, 25) as $i) {
            Payroll::factory()->create(['period' => sprintf('2026-%02d', ($i % 12) + 1), 'status' => Payroll::DRAFT]);
        }

        $response = $this->actingAs($finance)->get(route('hr.payroll.index', ['status' => Payroll::DRAFT]));

        $response->assertOk();
        $this->assertTrue($response->viewData('payrolls')->hasPages());
    }
}
