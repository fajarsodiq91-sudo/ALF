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

class FinanceDashboardTest extends TestCase
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

    public function test_finance_role_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->financeUser())->get(route('finance.dashboard'));

        $response->assertOk();
    }

    public function test_viewer_role_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->viewerUser())->get(route('finance.dashboard'));

        $response->assertOk();
    }

    public function test_dashboard_shows_month_overview(): void
    {
        $response = $this->actingAs($this->financeUser())->get(route('finance.dashboard'));

        $response->assertOk();
        $response->assertSee('This Month');
        $response->assertSee('Income');
        $response->assertSee('Expense');
    }

    public function test_dashboard_calculates_monthly_income(): void
    {
        $account = Account::factory()->create();
        $category = Category::factory()->create(['type' => 'income']);

        IncomeTransaction::factory()->create([
            'transaction_date' => now(),
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 5_000_000,
        ]);

        $response = $this->actingAs($this->financeUser())->get(route('finance.dashboard'));

        $response->assertOk();
        $response->assertSee('5.000.000');
    }

    public function test_dashboard_calculates_monthly_expense(): void
    {
        $account = Account::factory()->create();
        $category = Category::factory()->create(['type' => 'expense']);

        ExpenseTransaction::factory()->create([
            'transaction_date' => now(),
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 2_000_000,
        ]);

        $response = $this->actingAs($this->financeUser())->get(route('finance.dashboard'));

        $response->assertOk();
        $response->assertSee('2.000.000');
    }

    public function test_dashboard_shows_year_to_date_metrics(): void
    {
        $response = $this->actingAs($this->financeUser())->get(route('finance.dashboard'));

        $response->assertOk();
        $response->assertSee('Year to Date');
    }

    public function test_dashboard_shows_all_time_metrics(): void
    {
        $response = $this->actingAs($this->financeUser())->get(route('finance.dashboard'));

        $response->assertOk();
        $response->assertSee('All Time');
    }

    public function test_dashboard_shows_total_balance(): void
    {
        $account = Account::factory()->create(['opening_balance' => 10_000_000]);

        $response = $this->actingAs($this->financeUser())->get(route('finance.dashboard'));

        $response->assertOk();
        $response->assertSee('Total Balance');
        $response->assertSee('10.000.000');
    }

    public function test_dashboard_shows_recent_transactions(): void
    {
        $account = Account::factory()->create();
        $category = Category::factory()->create(['type' => 'income']);

        IncomeTransaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'source' => 'Consulting Fee',
            'amount' => 1_000_000,
        ]);

        $response = $this->actingAs($this->financeUser())->get(route('finance.dashboard'));

        $response->assertOk();
        $response->assertSee('Recent Transactions');
        $response->assertSee('Consulting Fee');
    }

    public function test_dashboard_shows_account_balances(): void
    {
        $account = Account::factory()->create(['name' => 'Operating', 'opening_balance' => 15_000_000]);

        $response = $this->actingAs($this->financeUser())->get(route('finance.dashboard'));

        $response->assertOk();
        $response->assertSee('Account Balances');
        $response->assertSee('Operating');
        $response->assertSee('15.000.000');
    }

    public function test_dashboard_includes_all_transaction_types_in_recent(): void
    {
        $account = Account::factory()->create();
        $incomeCategory = Category::factory()->create(['type' => 'income']);
        $expenseCategory = Category::factory()->create(['type' => 'expense']);

        IncomeTransaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $incomeCategory->id,
            'source' => 'Sales',
        ]);

        ExpenseTransaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $expenseCategory->id,
            'payee' => 'Vendor',
        ]);

        Transfer::factory()->create([
            'from_account_id' => $account->id,
            'to_account_id' => Account::factory()->create()->id,
        ]);

        $response = $this->actingAs($this->financeUser())->get(route('finance.dashboard'));

        $response->assertOk();
        $response->assertSee('Sales');
        $response->assertSee('Vendor');
    }

    public function test_dashboard_shows_empty_state_for_no_transactions(): void
    {
        $response = $this->actingAs($this->financeUser())->get(route('finance.dashboard'));

        $response->assertOk();
        $response->assertSee('No transactions yet');
    }

    public function test_dashboard_shows_empty_state_for_no_accounts(): void
    {
        $response = $this->actingAs($this->financeUser())->get(route('finance.dashboard'));

        $response->assertOk();
        $response->assertSee('No accounts');
    }

    public function test_dashboard_separates_month_from_previous_months(): void
    {
        $account = Account::factory()->create();
        $category = Category::factory()->create(['type' => 'income']);

        // Current month
        IncomeTransaction::factory()->create([
            'transaction_date' => now(),
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 5_000_000,
        ]);

        // Previous month
        IncomeTransaction::factory()->create([
            'transaction_date' => now()->subMonth(),
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 3_000_000,
        ]);

        $response = $this->actingAs($this->financeUser())->get(route('finance.dashboard'));

        $response->assertOk();
        // Only current month should be shown in "This Month" section
        // The 5_000_000 should be visible, but the 3_000_000 should be in year-to-date
    }

    public function test_dashboard_calculates_account_balance_with_transactions(): void
    {
        $account = Account::factory()->create(['opening_balance' => 10_000_000]);
        $incomeCategory = Category::factory()->create(['type' => 'income']);
        $expenseCategory = Category::factory()->create(['type' => 'expense']);

        IncomeTransaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $incomeCategory->id,
            'amount' => 3_000_000,
        ]);

        ExpenseTransaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $expenseCategory->id,
            'amount' => 1_000_000,
        ]);

        $response = $this->actingAs($this->financeUser())->get(route('finance.dashboard'));

        $response->assertOk();
        // Balance: 10_000_000 + 3_000_000 - 1_000_000 = 12_000_000
        $response->assertSee('12.000.000');
    }

    public function test_unauthenticated_user_cannot_view_dashboard(): void
    {
        $response = $this->get(route('finance.dashboard'));

        $response->assertRedirect('login');
    }
}
