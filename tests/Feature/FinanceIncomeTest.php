<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\IncomeTransaction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FinanceIncomeTest extends TestCase
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

    public function test_finance_role_can_view_income_list(): void
    {
        IncomeTransaction::factory()->count(3)->create();
        $user = $this->financeUser();

        $response = $this->actingAs($user)->get(route('finance.income'));

        $response->assertOk();
        $response->assertSee('Record income transactions');
    }

    public function test_finance_role_can_create_income_transaction(): void
    {
        $account = Account::factory()->create();
        $category = Category::factory()->income()->create();

        $response = $this->actingAs($this->financeUser())->post(route('finance.income.store'), [
            'transaction_date' => '2025-09-17',
            'amount' => 5_000_000,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'source' => 'PT Maju Sejahtera',
            'description' => 'Consulting service fee',
            'payment_method' => 'Bank Transfer',
        ]);

        $response->assertRedirect(route('finance.income'));
        $this->assertDatabaseHas('income_transactions', [
            'amount' => 5_000_000,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'source' => 'PT Maju Sejahtera',
        ]);
        $this->assertTrue(IncomeTransaction::latest()->first()->transaction_number !== null);
    }

    public function test_income_transaction_creation_requires_valid_data(): void
    {
        $response = $this->actingAs($this->financeUser())->post(route('finance.income.store'), [
            'transaction_date' => 'not-a-date',
            'amount' => -5,
            'account_id' => 999,
            'category_id' => 999,
            'source' => '',
            'payment_method' => '',
        ]);

        $response->assertSessionHasErrors(['transaction_date', 'amount', 'account_id', 'category_id', 'source', 'payment_method']);
        $this->assertDatabaseCount('income_transactions', 0);
    }

    public function test_income_transaction_requires_income_category(): void
    {
        $account = Account::factory()->create();
        $expenseCategory = Category::factory()->expense()->create();

        $response = $this->actingAs($this->financeUser())->post(route('finance.income.store'), [
            'transaction_date' => '2025-09-17',
            'amount' => 1_000_000,
            'account_id' => $account->id,
            'category_id' => $expenseCategory->id,
            'source' => 'Test Source',
            'payment_method' => 'Bank Transfer',
        ]);

        $response->assertSessionHasErrors(['category_id']);
        $this->assertDatabaseCount('income_transactions', 0);
    }

    public function test_viewer_role_cannot_create_income_transaction(): void
    {
        $account = Account::factory()->create();
        $category = Category::factory()->income()->create();

        $response = $this->actingAs($this->viewerUser())->post(route('finance.income.store'), [
            'transaction_date' => '2025-09-17',
            'amount' => 1_000_000,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'source' => 'Test Source',
            'payment_method' => 'Bank Transfer',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('income_transactions', 0);
    }

    public function test_viewer_role_cannot_access_create_form(): void
    {
        $response = $this->actingAs($this->viewerUser())->get(route('finance.income.create'));

        $response->assertForbidden();
    }

    public function test_finance_role_can_update_income_transaction(): void
    {
        $transaction = IncomeTransaction::factory()->create(['amount' => 1_000_000]);
        $newCategory = Category::factory()->income()->create();

        $response = $this->actingAs($this->financeUser())->put(route('finance.income.update', $transaction), [
            'transaction_date' => $transaction->transaction_date->format('Y-m-d'),
            'amount' => 2_000_000,
            'account_id' => $transaction->account_id,
            'category_id' => $newCategory->id,
            'source' => 'Updated Source',
            'payment_method' => 'Wire Transfer',
        ]);

        $response->assertRedirect(route('finance.income'));
        $this->assertDatabaseHas('income_transactions', [
            'id' => $transaction->id,
            'amount' => 2_000_000,
            'source' => 'Updated Source',
            'payment_method' => 'Wire Transfer',
        ]);
    }

    public function test_finance_role_can_delete_income_transaction(): void
    {
        $transaction = IncomeTransaction::factory()->create();

        $response = $this->actingAs($this->financeUser())->delete(route('finance.income.destroy', $transaction));

        $response->assertRedirect(route('finance.income'));
        $this->assertDatabaseMissing('income_transactions', ['id' => $transaction->id]);
    }

    public function test_income_transaction_is_auto_numbered(): void
    {
        $account = Account::factory()->create();
        $category = Category::factory()->income()->create();

        $response = $this->actingAs($this->financeUser())->post(route('finance.income.store'), [
            'transaction_date' => '2025-09-17',
            'amount' => 1_000_000,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'source' => 'Test Source',
            'payment_method' => 'Bank Transfer',
        ]);

        $response->assertRedirect();
        $transaction = IncomeTransaction::latest()->first();
        $this->assertNotNull($transaction);
        $this->assertStringStartsWith('INC-', $transaction->transaction_number);
    }

    public function test_created_by_is_set_automatically(): void
    {
        $user = $this->financeUser();
        $account = Account::factory()->create();
        $category = Category::factory()->income()->create();

        $this->actingAs($user)->post(route('finance.income.store'), [
            'transaction_date' => '2025-09-17',
            'amount' => 1_000_000,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'source' => 'Test Source',
            'payment_method' => 'Bank Transfer',
        ]);

        $transaction = IncomeTransaction::latest()->first();
        $this->assertEquals($user->id, $transaction->created_by);
    }
}
