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

class FinanceTransactionLedgerTest extends TestCase
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

    public function test_finance_role_can_view_transaction_ledger(): void
    {
        IncomeTransaction::factory()->create();
        ExpenseTransaction::factory()->create();
        Transfer::factory()->create();

        $response = $this->actingAs($this->financeUser())->get(route('finance.transactions'));

        $response->assertOk();
    }

    public function test_ledger_displays_income_transactions(): void
    {
        $account = Account::factory()->create();
        $category = Category::factory()->create(['type' => 'income']);
        IncomeTransaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'source' => 'Consulting Client',
            'amount' => 5_000_000,
        ]);

        $response = $this->actingAs($this->financeUser())->get(route('finance.transactions'));

        $response->assertOk();
        $response->assertSee('Income');
        $response->assertSee('Consulting Client');
        $response->assertSee('+Rp');
    }

    public function test_ledger_displays_expense_transactions(): void
    {
        $account = Account::factory()->create();
        $category = Category::factory()->create(['type' => 'expense']);
        ExpenseTransaction::factory()->create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'payee' => 'Office Supplies Vendor',
            'amount' => 2_500_000,
        ]);

        $response = $this->actingAs($this->financeUser())->get(route('finance.transactions'));

        $response->assertOk();
        $response->assertSee('Expense');
        $response->assertSee('Office Supplies Vendor');
        $response->assertSee('-Rp');
    }

    public function test_ledger_displays_transfer_transactions(): void
    {
        $fromAccount = Account::factory()->create(['name' => 'Operating']);
        $toAccount = Account::factory()->create(['name' => 'Savings']);
        Transfer::factory()->create([
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
            'amount' => 10_000_000,
        ]);

        $response = $this->actingAs($this->financeUser())->get(route('finance.transactions'));

        $response->assertOk();
        $response->assertSee('Transfer');
        $response->assertSee('Operating');
        $response->assertSee('Savings');
    }

    public function test_ledger_displays_all_transaction_types_together(): void
    {
        $account = Account::factory()->create();
        $incomeCategory = Category::factory()->create(['type' => 'income']);
        $expenseCategory = Category::factory()->create(['type' => 'expense']);

        IncomeTransaction::factory()->create(['account_id' => $account->id, 'category_id' => $incomeCategory->id]);
        ExpenseTransaction::factory()->create(['account_id' => $account->id, 'category_id' => $expenseCategory->id]);
        Transfer::factory()->create(['from_account_id' => $account->id]);

        $response = $this->actingAs($this->financeUser())->get(route('finance.transactions'));

        $response->assertOk();
        $response->assertSee('Income');
        $response->assertSee('Expense');
        $response->assertSee('Transfer');
    }

    public function test_ledger_displays_empty_state_when_no_transactions(): void
    {
        $response = $this->actingAs($this->financeUser())->get(route('finance.transactions'));

        $response->assertOk();
        $response->assertSee('No transactions yet');
    }

    public function test_viewer_role_can_view_ledger(): void
    {
        IncomeTransaction::factory()->create();

        $response = $this->actingAs($this->viewerUser())->get(route('finance.transactions'));

        $response->assertOk();
    }

    public function test_unauthenticated_user_cannot_view_ledger(): void
    {
        $response = $this->get(route('finance.transactions'));

        $response->assertRedirect('login');
    }

    public function test_ledger_sorts_transactions_by_date_descending(): void
    {
        $account = Account::factory()->create();
        $category = Category::factory()->create(['type' => 'income']);

        IncomeTransaction::factory()->create([
            'transaction_date' => '2025-01-01',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'source' => 'Early',
        ]);

        IncomeTransaction::factory()->create([
            'transaction_date' => '2025-12-31',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'source' => 'Late',
        ]);

        $response = $this->actingAs($this->financeUser())->get(route('finance.transactions'));

        $response->assertOk();
        // Latest transaction should appear first (higher in HTML)
        $content = $response->getContent();
        $latePos = strpos($content, 'Late');
        $earlyPos = strpos($content, 'Early');
        $this->assertLessThan($earlyPos, $latePos);
    }
}
