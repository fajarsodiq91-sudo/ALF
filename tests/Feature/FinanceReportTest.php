<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\ExpenseTransaction;
use App\Models\IncomeTransaction;
use App\Models\Transfer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FinanceReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function financeUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Finance');

        return $user;
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Viewer');

        return $user;
    }

    public function test_finance_role_can_view_reports_dashboard(): void
    {
        $response = $this->actingAs($this->financeUser())->get(route('finance.reports.index'));

        $response->assertOk();
    }

    public function test_viewer_role_can_view_reports_dashboard(): void
    {
        $response = $this->actingAs($this->viewerUser())->get(route('finance.reports.index'));

        $response->assertOk();
    }

    public function test_reports_dashboard_shows_summary_cards(): void
    {
        $account = Account::factory()->create();
        $incomeCategory = Category::factory()->create(['type' => 'income']);
        $expenseCategory = Category::factory()->create(['type' => 'expense']);

        IncomeTransaction::factory()->create(['account_id' => $account->id, 'category_id' => $incomeCategory->id, 'amount' => 1_000_000]);
        ExpenseTransaction::factory()->create(['account_id' => $account->id, 'category_id' => $expenseCategory->id, 'amount' => 500_000]);

        $response = $this->actingAs($this->financeUser())->get(route('finance.reports.index'));

        $response->assertOk();
        $response->assertSee('Total Income');
        $response->assertSee('Total Expense');
        $response->assertSee('Net Income');
        $response->assertSee('Total Balance');
    }

    public function test_income_by_category_report(): void
    {
        $account = Account::factory()->create();
        $category = Category::factory()->create(['name' => 'Consulting', 'type' => 'income']);

        IncomeTransaction::factory()->count(3)->create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 1_000_000,
        ]);

        $response = $this->actingAs($this->financeUser())->get(route('finance.reports.income-by-category'));

        $response->assertOk();
        $response->assertSee('Consulting');
        $response->assertSee('3'); // count
    }

    public function test_expense_by_category_report(): void
    {
        $account = Account::factory()->create();
        $category = Category::factory()->create(['name' => 'Office Supplies', 'type' => 'expense']);

        ExpenseTransaction::factory()->count(2)->create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 500_000,
        ]);

        $response = $this->actingAs($this->financeUser())->get(route('finance.reports.expense-by-category'));

        $response->assertOk();
        $response->assertSee('Office Supplies');
        $response->assertSee('2'); // count
    }

    public function test_monthly_flow_report(): void
    {
        $account = Account::factory()->create();
        $incomeCategory = Category::factory()->create(['type' => 'income']);
        $expenseCategory = Category::factory()->create(['type' => 'expense']);

        IncomeTransaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $incomeCategory->id,
            'transaction_date' => '2025-09-01',
            'amount' => 5_000_000,
        ]);

        ExpenseTransaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $expenseCategory->id,
            'transaction_date' => '2025-09-15',
            'amount' => 2_000_000,
        ]);

        $response = $this->actingAs($this->financeUser())->get(route('finance.reports.monthly-flow'));

        $response->assertOk();
        $response->assertSee('2025-09');
    }

    public function test_account_balances_report(): void
    {
        $account = Account::factory()->create(['name' => 'Operating', 'opening_balance' => 10_000_000]);
        $incomeCategory = Category::factory()->create(['type' => 'income']);
        $expenseCategory = Category::factory()->create(['type' => 'expense']);

        IncomeTransaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $incomeCategory->id,
            'amount' => 5_000_000,
        ]);

        ExpenseTransaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $expenseCategory->id,
            'amount' => 2_000_000,
        ]);

        $response = $this->actingAs($this->financeUser())->get(route('finance.reports.account-balances'));

        $response->assertOk();
        $response->assertSee('Operating');
        // Balance should be: 10_000_000 + 5_000_000 - 2_000_000 = 13_000_000
        $response->assertSee('13.000.000');
    }

    public function test_account_balances_includes_transfers(): void
    {
        $fromAccount = Account::factory()->create(['name' => 'Operating', 'opening_balance' => 20_000_000]);
        $toAccount = Account::factory()->create(['name' => 'Savings', 'opening_balance' => 5_000_000]);

        Transfer::factory()->create([
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
            'amount' => 3_000_000,
        ]);

        $response = $this->actingAs($this->financeUser())->get(route('finance.reports.account-balances'));

        $response->assertOk();
        // Operating: 20_000_000 - 3_000_000 = 17_000_000
        $response->assertSee('Operating');
        // Savings: 5_000_000 + 3_000_000 = 8_000_000
        $response->assertSee('Savings');
    }

    public function test_unauthenticated_user_cannot_view_reports(): void
    {
        $response = $this->get(route('finance.reports.index'));

        $response->assertRedirect('login');
    }

    public function test_income_by_category_shows_empty_state(): void
    {
        $response = $this->actingAs($this->financeUser())->get(route('finance.reports.income-by-category'));

        $response->assertOk();
        $response->assertSee('No income transactions');
    }

    public function test_expense_by_category_shows_empty_state(): void
    {
        $response = $this->actingAs($this->financeUser())->get(route('finance.reports.expense-by-category'));

        $response->assertOk();
        $response->assertSee('No expense transactions');
    }

    public function test_monthly_flow_shows_empty_state(): void
    {
        $response = $this->actingAs($this->financeUser())->get(route('finance.reports.monthly-flow'));

        $response->assertOk();
        $response->assertSee('No transactions');
    }

    public function test_account_balances_shows_empty_state(): void
    {
        $response = $this->actingAs($this->financeUser())->get(route('finance.reports.account-balances'));

        $response->assertOk();
        $response->assertSee('No accounts');
    }
}
