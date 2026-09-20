<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\ExpenseTransaction;
use App\Models\IncomeTransaction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FinanceAccountsCategoriesTest extends TestCase
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

    // --- Accounts -----------------------------------------------------

    public function test_finance_role_can_create_an_account(): void
    {
        $response = $this->actingAs($this->financeUser())->post(route('finance.accounts.store'), [
            'name' => 'Bank BCA',
            'account_type' => 'bank',
            'account_number' => '1234567890',
            'opening_balance' => 5_000_000,
            'is_active' => '1',
            'description' => 'Main operating account',
        ]);

        $response->assertRedirect(route('finance.accounts'));
        $this->assertDatabaseHas('accounts', ['name' => 'Bank BCA', 'account_type' => 'bank']);
    }

    public function test_account_creation_requires_name_and_valid_type(): void
    {
        $response = $this->actingAs($this->financeUser())->post(route('finance.accounts.store'), [
            'name' => '',
            'account_type' => 'not-a-type',
            'opening_balance' => -5,
        ]);

        $response->assertSessionHasErrors(['name', 'account_type', 'opening_balance']);
        $this->assertDatabaseCount('accounts', 0);
    }

    public function test_viewer_role_cannot_create_an_account(): void
    {
        $response = $this->actingAs($this->viewerUser())->post(route('finance.accounts.store'), [
            'name' => 'Bank BCA',
            'account_type' => 'bank',
            'opening_balance' => 0,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('accounts', 0);
    }

    public function test_viewer_role_can_view_accounts_list_but_not_the_create_form(): void
    {
        Account::factory()->create();
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)->get(route('finance.accounts'))->assertOk();
        $this->actingAs($viewer)->get(route('finance.accounts.create'))->assertForbidden();
    }

    public function test_finance_role_can_update_an_account(): void
    {
        $account = Account::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->financeUser())->put(route('finance.accounts.update', $account), [
            'name' => 'New Name',
            'account_type' => $account->account_type,
            'opening_balance' => $account->opening_balance,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('finance.accounts'));
        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'name' => 'New Name']);
    }

    public function test_account_without_transactions_can_be_deleted(): void
    {
        $account = Account::factory()->create();

        $response = $this->actingAs($this->financeUser())->delete(route('finance.accounts.destroy', $account));

        $response->assertRedirect(route('finance.accounts'));
        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }

    public function test_account_with_transactions_cannot_be_deleted(): void
    {
        $income = IncomeTransaction::factory()->create();

        $response = $this->actingAs($this->financeUser())->delete(route('finance.accounts.destroy', $income->account));

        $response->assertRedirect(route('finance.accounts'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('accounts', ['id' => $income->account_id]);
    }

    // --- Categories -----------------------------------------------------

    public function test_finance_role_can_create_a_category(): void
    {
        $response = $this->actingAs($this->financeUser())->post(route('finance.categories.store'), [
            'name' => 'Consulting',
            'type' => 'income',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('finance.categories'));
        $this->assertDatabaseHas('categories', ['name' => 'Consulting', 'type' => 'income']);
    }

    public function test_category_creation_requires_valid_type(): void
    {
        $response = $this->actingAs($this->financeUser())->post(route('finance.categories.store'), [
            'name' => 'Something',
            'type' => 'not-income-or-expense',
        ]);

        $response->assertSessionHasErrors(['type']);
    }

    public function test_viewer_role_cannot_create_a_category(): void
    {
        $response = $this->actingAs($this->viewerUser())->post(route('finance.categories.store'), [
            'name' => 'Consulting',
            'type' => 'income',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('categories', ['name' => 'Consulting']);
    }

    public function test_category_with_transactions_cannot_be_deleted(): void
    {
        $expense = ExpenseTransaction::factory()->create();

        $response = $this->actingAs($this->financeUser())->delete(route('finance.categories.destroy', $expense->category));

        $response->assertRedirect(route('finance.categories'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $expense->category_id]);
    }

    public function test_category_without_transactions_can_be_deleted(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->financeUser())->delete(route('finance.categories.destroy', $category));

        $response->assertRedirect(route('finance.categories'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
