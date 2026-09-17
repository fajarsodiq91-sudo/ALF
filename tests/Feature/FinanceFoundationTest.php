<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\ExpenseTransaction;
use App\Models\IncomeTransaction;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_has_expected_attributes_and_defaults(): void
    {
        $account = Account::factory()->create(['opening_balance' => 1_000_000]);

        $this->assertEquals('1000000.00', $account->opening_balance);
        $this->assertTrue($account->is_active);
    }

    public function test_category_type_is_restricted_to_income_or_expense(): void
    {
        $income = Category::factory()->income()->create();
        $expense = Category::factory()->expense()->create();

        $this->assertEquals('income', $income->type);
        $this->assertEquals('expense', $expense->type);

        $this->expectException(QueryException::class);
        Category::factory()->create(['type' => 'not-a-real-type']);
    }

    public function test_transaction_number_must_be_unique(): void
    {
        IncomeTransaction::factory()->create(['transaction_number' => 'INC-202609-0001']);

        $this->expectException(QueryException::class);
        IncomeTransaction::factory()->create(['transaction_number' => 'INC-202609-0001']);
    }

    public function test_income_transaction_relationships_resolve(): void
    {
        $income = IncomeTransaction::factory()->create();

        $this->assertInstanceOf(Account::class, $income->account);
        $this->assertInstanceOf(Category::class, $income->category);
        $this->assertInstanceOf(User::class, $income->createdBy);
        $this->assertEquals('income', $income->category->type);
    }

    public function test_expense_transaction_relationships_resolve(): void
    {
        $expense = ExpenseTransaction::factory()->create();

        $this->assertInstanceOf(Account::class, $expense->account);
        $this->assertInstanceOf(Category::class, $expense->category);
        $this->assertEquals('expense', $expense->category->type);
    }

    public function test_transfer_relationships_resolve_to_distinct_accounts(): void
    {
        $from = Account::factory()->create();
        $to = Account::factory()->create();

        $transfer = Transfer::factory()->create([
            'from_account_id' => $from->id,
            'to_account_id' => $to->id,
        ]);

        $this->assertTrue($transfer->fromAccount->is($from));
        $this->assertTrue($transfer->toAccount->is($to));
    }

    public function test_account_cannot_be_deleted_while_referenced_by_a_transaction(): void
    {
        $income = IncomeTransaction::factory()->create();

        $this->expectException(QueryException::class);
        $income->account->delete();
    }

    public function test_category_cannot_be_deleted_while_referenced_by_a_transaction(): void
    {
        $expense = ExpenseTransaction::factory()->create();

        $this->expectException(QueryException::class);
        $expense->category->delete();
    }

    public function test_account_balance_equals_opening_balance_when_no_transactions(): void
    {
        $account = Account::factory()->create(['opening_balance' => 500_000]);

        $this->assertEquals(500_000.0, $account->currentBalance());
    }

    public function test_account_balance_formula_income_expense_and_transfers(): void
    {
        $account = Account::factory()->create(['opening_balance' => 1_000_000]);
        $other = Account::factory()->create(['opening_balance' => 0]);

        IncomeTransaction::factory()->create([
            'account_id' => $account->id,
            'amount' => 500_000,
        ]);

        ExpenseTransaction::factory()->create([
            'account_id' => $account->id,
            'amount' => 200_000,
        ]);

        // Money leaving $account into $other.
        Transfer::factory()->create([
            'from_account_id' => $account->id,
            'to_account_id' => $other->id,
            'amount' => 100_000,
        ]);

        // Money coming into $account from $other.
        Transfer::factory()->create([
            'from_account_id' => $other->id,
            'to_account_id' => $account->id,
            'amount' => 50_000,
        ]);

        // 1,000,000 + 500,000 - 200,000 - 100,000 + 50,000 = 1,250,000
        $this->assertEquals(1_250_000.0, $account->currentBalance());
    }

    public function test_transfers_do_not_change_the_total_balance_across_all_accounts(): void
    {
        $a = Account::factory()->create(['opening_balance' => 1_000_000]);
        $b = Account::factory()->create(['opening_balance' => 500_000]);

        $totalBefore = $a->currentBalance() + $b->currentBalance();

        Transfer::factory()->create([
            'from_account_id' => $a->id,
            'to_account_id' => $b->id,
            'amount' => 300_000,
        ]);

        $totalAfter = $a->fresh()->currentBalance() + $b->fresh()->currentBalance();

        $this->assertEquals($totalBefore, $totalAfter);
    }
}
