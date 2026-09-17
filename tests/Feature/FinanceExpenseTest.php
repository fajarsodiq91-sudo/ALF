<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\ExpenseTransaction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FinanceExpenseTest extends TestCase
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

    public function test_finance_role_can_view_expense_list(): void
    {
        ExpenseTransaction::factory()->count(3)->create();
        $user = $this->financeUser();

        $response = $this->actingAs($user)->get(route('finance.expenses'));

        $response->assertOk();
        $response->assertSee('Record expense transactions');
    }

    public function test_finance_role_can_create_expense_transaction(): void
    {
        $account = Account::factory()->create();
        $category = Category::factory()->expense()->create();

        $response = $this->actingAs($this->financeUser())->post(route('finance.expenses.store'), [
            'transaction_date' => '2025-09-17',
            'amount' => 2_500_000,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'payee' => 'Acme Office Supplies',
            'payment_method' => 'Cash',
        ]);

        $response->assertRedirect(route('finance.expenses'));
        $this->assertDatabaseHas('expense_transactions', [
            'amount' => 2_500_000,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'payee' => 'Acme Office Supplies',
        ]);
        $this->assertTrue(ExpenseTransaction::latest()->first()->transaction_number !== null);
    }

    public function test_expense_transaction_creation_requires_valid_data(): void
    {
        $response = $this->actingAs($this->financeUser())->post(route('finance.expenses.store'), [
            'transaction_date' => 'not-a-date',
            'amount' => -5,
            'account_id' => 999,
            'category_id' => 999,
            'payee' => '',
            'payment_method' => '',
        ]);

        $response->assertSessionHasErrors(['transaction_date', 'amount', 'account_id', 'category_id', 'payee', 'payment_method']);
        $this->assertDatabaseCount('expense_transactions', 0);
    }

    public function test_expense_transaction_requires_expense_category(): void
    {
        $account = Account::factory()->create();
        $incomeCategory = Category::factory()->income()->create();

        $response = $this->actingAs($this->financeUser())->post(route('finance.expenses.store'), [
            'transaction_date' => '2025-09-17',
            'amount' => 1_000_000,
            'account_id' => $account->id,
            'category_id' => $incomeCategory->id,
            'payee' => 'Test',
            'payment_method' => 'Cash',
        ]);

        $response->assertSessionHasErrors(['category_id']);
        $this->assertDatabaseCount('expense_transactions', 0);
    }

    public function test_viewer_role_cannot_create_expense_transaction(): void
    {
        $account = Account::factory()->create();
        $category = Category::factory()->expense()->create();

        $response = $this->actingAs($this->viewerUser())->post(route('finance.expenses.store'), [
            'transaction_date' => '2025-09-17',
            'amount' => 1_000_000,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'payee' => 'Test',
            'payment_method' => 'Cash',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('expense_transactions', 0);
    }

    public function test_viewer_role_cannot_access_create_form(): void
    {
        $response = $this->actingAs($this->viewerUser())->get(route('finance.expenses.create'));

        $response->assertForbidden();
    }

    public function test_finance_role_can_update_expense_transaction(): void
    {
        $transaction = ExpenseTransaction::factory()->create(['amount' => 1_000_000]);
        $newCategory = Category::factory()->expense()->create();

        $response = $this->actingAs($this->financeUser())->put(route('finance.expenses.update', $transaction), [
            'transaction_date' => $transaction->transaction_date->format('Y-m-d'),
            'amount' => 1_500_000,
            'account_id' => $transaction->account_id,
            'category_id' => $newCategory->id,
            'payee' => 'Updated Payee',
            'payment_method' => 'Bank Transfer',
        ]);

        $response->assertRedirect(route('finance.expenses'));
        $this->assertDatabaseHas('expense_transactions', [
            'id' => $transaction->id,
            'amount' => 1_500_000,
            'payee' => 'Updated Payee',
        ]);
    }

    public function test_finance_role_can_delete_expense_transaction(): void
    {
        $transaction = ExpenseTransaction::factory()->create();

        $response = $this->actingAs($this->financeUser())->delete(route('finance.expenses.destroy', $transaction));

        $response->assertRedirect(route('finance.expenses'));
        $this->assertDatabaseMissing('expense_transactions', ['id' => $transaction->id]);
    }

    public function test_expense_transaction_is_auto_numbered(): void
    {
        $account = Account::factory()->create();
        $category = Category::factory()->expense()->create();

        $response = $this->actingAs($this->financeUser())->post(route('finance.expenses.store'), [
            'transaction_date' => '2025-09-17',
            'amount' => 1_000_000,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'payee' => 'Test Vendor',
            'payment_method' => 'Cash',
        ]);

        $response->assertRedirect();
        $transaction = ExpenseTransaction::latest()->first();
        $this->assertNotNull($transaction);
        $this->assertStringStartsWith('EXP-', $transaction->transaction_number);
    }

    public function test_created_by_is_set_automatically(): void
    {
        $user = $this->financeUser();
        $account = Account::factory()->create();
        $category = Category::factory()->expense()->create();

        $this->actingAs($user)->post(route('finance.expenses.store'), [
            'transaction_date' => '2025-09-17',
            'amount' => 1_000_000,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'payee' => 'Test Vendor',
            'payment_method' => 'Cash',
        ]);

        $transaction = ExpenseTransaction::latest()->first();
        $this->assertEquals($user->id, $transaction->created_by);
    }
}
