<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\ExpenseTransaction;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\Transfer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FinanceLoanAndBankFeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function user(string $role = 'Finance'): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_transfer_fee_is_recorded_as_expense_and_reduces_source_balance(): void
    {
        $from = Account::factory()->create(['opening_balance' => 1_000_000]);
        $to = Account::factory()->create(['opening_balance' => 0]);

        $this->actingAs($this->user())->post(route('finance.transfers.store'), [
            'transfer_date' => '2026-09-20',
            'amount' => 400_000,
            'fee' => 6_500,
            'from_account_id' => $from->id,
            'to_account_id' => $to->id,
        ])->assertRedirect(route('finance.transfers'));

        $transfer = Transfer::firstOrFail();
        $expense = $transfer->feeExpense;
        $this->assertNotNull($expense);
        $this->assertEquals(6_500, $expense->amount);
        $this->assertEquals($from->id, $expense->account_id);
        $this->assertSame('Bank Charges', $expense->category->name);
        $this->assertEquals(1_000_000 - 400_000 - 6_500, $from->currentBalance());
        $this->assertEquals(400_000, $to->currentBalance());
    }

    public function test_transfer_fee_can_be_changed_removed_and_cascades_on_delete(): void
    {
        $from = Account::factory()->create();
        $to = Account::factory()->create();
        $user = $this->user();
        $payload = ['transfer_date' => '2026-09-20', 'amount' => 100_000, 'from_account_id' => $from->id, 'to_account_id' => $to->id];

        $this->actingAs($user)->post(route('finance.transfers.store'), [...$payload, 'fee' => 5_000]);
        $transfer = Transfer::firstOrFail();

        $this->actingAs($user)->put(route('finance.transfers.update', $transfer), [...$payload, 'fee' => 7_000]);
        $this->assertEquals(7_000, $transfer->fresh()->feeExpense->amount);
        $this->assertSame(1, ExpenseTransaction::count());

        $this->actingAs($user)->put(route('finance.transfers.update', $transfer), [...$payload, 'fee' => 0]);
        $this->assertNull($transfer->fresh()->feeExpense);
        $this->assertSame(0, ExpenseTransaction::count());

        $this->actingAs($user)->put(route('finance.transfers.update', $transfer), [...$payload, 'fee' => 3_000]);
        $this->actingAs($user)->delete(route('finance.transfers.destroy', $transfer));
        $this->assertSame(0, ExpenseTransaction::count());
    }

    public function test_transfer_without_fee_creates_no_expense(): void
    {
        $from = Account::factory()->create();
        $to = Account::factory()->create();

        $this->actingAs($this->user())->post(route('finance.transfers.store'), [
            'transfer_date' => '2026-09-20', 'amount' => 100_000, 'from_account_id' => $from->id, 'to_account_id' => $to->id,
        ]);

        $this->assertSame(0, ExpenseTransaction::count());
    }

    public function test_owner_borrowing_from_company_reduces_then_restores_account_balance(): void
    {
        $account = Account::factory()->create(['opening_balance' => 10_000_000]);
        $user = $this->user();

        $this->actingAs($user)->post(route('finance.loans.store'), [
            'direction' => Loan::OWNER_BORROWS,
            'loan_date' => '2026-09-20',
            'account_id' => $account->id,
            'party_name' => 'Owner',
            'amount' => 3_000_000,
        ])->assertRedirect();

        $loan = Loan::firstOrFail();
        $this->assertEquals(7_000_000, $account->currentBalance());
        $this->assertEquals(3_000_000, $loan->outstandingAmount());

        $this->actingAs($user)->post(route('finance.loans.repayments.store', $loan), [
            'repayment_date' => '2026-10-01', 'account_id' => $account->id, 'amount' => 1_000_000,
        ])->assertRedirect(route('finance.loans.show', $loan));

        $this->assertEquals(8_000_000, $account->currentBalance());
        $this->assertEquals(2_000_000, $loan->fresh()->outstandingAmount());
    }

    public function test_company_borrowing_from_owner_increases_then_reduces_account_balance(): void
    {
        $account = Account::factory()->create(['opening_balance' => 1_000_000]);
        $loan = Loan::factory()->companyBorrows()->create(['account_id' => $account->id, 'amount' => 5_000_000]);

        $this->assertEquals(6_000_000, $account->currentBalance());

        LoanRepayment::factory()->create(['loan_id' => $loan->id, 'account_id' => $account->id, 'amount' => 2_000_000]);

        $this->assertEquals(4_000_000, $account->currentBalance());
        $this->assertEquals(3_000_000, $loan->fresh()->outstandingAmount());
    }

    public function test_loans_do_not_affect_income_or_expense_reports(): void
    {
        Loan::factory()->companyBorrows()->create(['amount' => 5_000_000]);
        Loan::factory()->create(['amount' => 2_000_000]);

        $this->actingAs($this->user())->get(route('finance.reports.index'))->assertOk();
        $this->assertSame(0, ExpenseTransaction::count());
    }

    public function test_repayment_cannot_exceed_outstanding(): void
    {
        $loan = Loan::factory()->create(['amount' => 1_000_000]);

        $this->actingAs($this->user())->post(route('finance.loans.repayments.store', $loan), [
            'repayment_date' => '2026-10-01', 'account_id' => $loan->account_id, 'amount' => 1_000_001,
        ])->assertSessionHasErrors('amount');

        $this->assertSame(0, LoanRepayment::count());
    }

    public function test_loan_with_repayments_cannot_be_deleted_or_reduced_below_repaid(): void
    {
        $loan = Loan::factory()->create(['amount' => 1_000_000]);
        LoanRepayment::factory()->create(['loan_id' => $loan->id, 'amount' => 400_000]);
        $user = $this->user();

        $this->actingAs($user)->delete(route('finance.loans.destroy', $loan))->assertSessionHas('error');
        $this->assertModelExists($loan);

        $this->actingAs($user)->put(route('finance.loans.update', $loan), [
            'direction' => $loan->direction, 'loan_date' => '2026-09-20', 'account_id' => $loan->account_id,
            'party_name' => 'Owner', 'amount' => 300_000,
        ])->assertSessionHasErrors('amount');
    }

    public function test_loan_pages_and_ledger_render_and_viewer_cannot_manage(): void
    {
        $loan = Loan::factory()->create();
        LoanRepayment::factory()->create(['loan_id' => $loan->id, 'amount' => 100_000]);
        $viewer = $this->user('Viewer');

        $this->actingAs($viewer)->get(route('finance.loans'))->assertOk()->assertSee($loan->loan_number);
        $this->actingAs($viewer)->get(route('finance.loans.show', $loan))->assertOk();
        $this->actingAs($viewer)->get(route('finance.transactions'))->assertOk()->assertSee('Repayment');
        $this->actingAs($viewer)->get(route('finance.reports.account-balances'))->assertOk();
        $this->actingAs($viewer)->get(route('finance.dashboard'))->assertOk();
        $this->actingAs($viewer)->get(route('finance.loans.create'))->assertForbidden();
        $this->actingAs($viewer)->post(route('finance.loans.store'), [])->assertForbidden();
    }

    public function test_bank_charges_category_exists_after_migration(): void
    {
        $this->assertTrue(Category::where('name', 'Bank Charges')->where('type', 'expense')->exists());
    }
}
