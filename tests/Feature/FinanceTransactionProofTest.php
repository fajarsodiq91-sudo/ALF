<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\ExpenseTransaction;
use App\Models\IncomeTransaction;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\TaxPayment;
use App\Models\Transfer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FinanceTransactionProofTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Storage::fake('local');
    }

    private function user(string $role = 'Finance'): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function pdf(string $name = 'bukti.pdf'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 100, 'application/pdf');
    }

    private function incomePayload(array $overrides = []): array
    {
        return [
            'transaction_date' => '2026-09-29',
            'amount' => 1_000_000,
            'account_id' => Account::factory()->create()->id,
            'category_id' => Category::factory()->income()->create()->id,
            'source' => 'PT Maju',
            'payment_method' => 'Bank Transfer',
            ...$overrides,
        ];
    }

    public function test_income_stores_uploaded_proof_and_link(): void
    {
        $this->actingAs($this->user())->post(route('finance.income.store'), $this->incomePayload([
            'proof' => $this->pdf(),
            'proof_url' => 'https://drive.google.com/file/d/abc',
        ]))->assertRedirect(route('finance.income'));

        $income = IncomeTransaction::firstOrFail();
        $this->assertSame('bukti.pdf', $income->proof_original_name);
        $this->assertSame('https://drive.google.com/file/d/abc', $income->proof_url);
        $this->assertStringStartsWith('finance-proofs/income/', $income->proof_path);
        Storage::disk('local')->assertExists($income->proof_path);

        $this->actingAs($this->user())->get(route('finance.income'))
            ->assertOk()
            ->assertSee($income->proofFileUrl())
            ->assertSee('https://drive.google.com/file/d/abc');
    }

    public function test_proof_file_is_only_served_to_finance_users(): void
    {
        $this->actingAs($this->user())->post(route('finance.income.store'), $this->incomePayload(['proof' => $this->pdf()]));
        $income = IncomeTransaction::firstOrFail();

        $this->actingAs($this->user())->get($income->proofFileUrl())->assertOk();
        $this->actingAs($this->user('Staff'))->get($income->proofFileUrl())->assertForbidden();
        $this->actingAs($this->user())
            ->get(route('finance.proofs.show', ['type' => 'income', 'id' => $income->id + 1]))
            ->assertNotFound();
    }

    public function test_new_upload_replaces_old_file_and_remove_clears_it(): void
    {
        $user = $this->user();
        $payload = $this->incomePayload();
        $this->actingAs($user)->post(route('finance.income.store'), [...$payload, 'proof' => $this->pdf('old.pdf')]);
        $income = IncomeTransaction::firstOrFail();
        $oldPath = $income->proof_path;

        $this->actingAs($user)->put(route('finance.income.update', $income), [...$payload, 'proof' => $this->pdf('new.pdf')]);
        $income->refresh();
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($income->proof_path);
        $this->assertSame('new.pdf', $income->proof_original_name);

        // Saving without a new file keeps the current one.
        $this->actingAs($user)->put(route('finance.income.update', $income), $payload);
        $this->assertSame('new.pdf', $income->fresh()->proof_original_name);

        $newPath = $income->proof_path;
        $this->actingAs($user)->put(route('finance.income.update', $income), [...$payload, 'remove_proof' => 1]);
        $this->assertNull($income->fresh()->proof_path);
        Storage::disk('local')->assertMissing($newPath);
    }

    public function test_deleting_a_transaction_deletes_its_proof_file(): void
    {
        $user = $this->user();
        $this->actingAs($user)->post(route('finance.income.store'), $this->incomePayload(['proof' => $this->pdf()]));
        $income = IncomeTransaction::firstOrFail();

        $this->actingAs($user)->delete(route('finance.income.destroy', $income));

        Storage::disk('local')->assertMissing($income->proof_path);
    }

    public function test_invalid_proof_is_rejected(): void
    {
        $this->actingAs($this->user())->post(route('finance.income.store'), $this->incomePayload([
            'proof' => UploadedFile::fake()->create('script.exe', 10),
            'proof_url' => 'javascript:alert(1)',
        ]))->assertSessionHasErrors(['proof', 'proof_url']);

        $this->assertSame(0, IncomeTransaction::count());
    }

    public function test_every_finance_transaction_type_accepts_proof(): void
    {
        $user = $this->user();
        $account = Account::factory()->create(['opening_balance' => 50_000_000]);
        $other = Account::factory()->create();
        $link = ['proof' => $this->pdf(), 'proof_url' => 'https://example.com/receipt'];

        $this->actingAs($user)->post(route('finance.expenses.store'), [
            'transaction_date' => '2026-09-29', 'amount' => 100_000, 'account_id' => $account->id,
            'category_id' => Category::factory()->expense()->create()->id, 'payee' => 'Toko', 'payment_method' => 'Cash', ...$link,
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->post(route('finance.transfers.store'), [
            'transfer_date' => '2026-09-29', 'amount' => 100_000, 'from_account_id' => $account->id, 'to_account_id' => $other->id,
            'proof' => $this->pdf(), 'proof_url' => 'https://example.com/receipt',
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->post(route('finance.loans.store'), [
            'direction' => Loan::OWNER_BORROWS, 'loan_date' => '2026-09-29', 'account_id' => $account->id,
            'party_name' => 'Owner', 'amount' => 1_000_000, 'proof' => $this->pdf(), 'proof_url' => 'https://example.com/receipt',
        ])->assertSessionHasNoErrors();

        $loan = Loan::firstOrFail();
        $this->actingAs($user)->post(route('finance.loans.repayments.store', $loan), [
            'repayment_date' => '2026-09-30', 'account_id' => $account->id, 'amount' => 500_000,
            'proof' => $this->pdf(), 'proof_url' => 'https://example.com/receipt',
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->post(route('finance.tax-payments.store'), [
            'tax_type' => 'vat', 'period' => '2026-08', 'payment_date' => '2026-09-10', 'account_id' => $account->id,
            'amount' => 100_000, 'proof' => $this->pdf(), 'proof_url' => 'https://example.com/receipt',
        ])->assertSessionHasNoErrors();

        $records = [
            ExpenseTransaction::whereNull('tax_payment_id')->firstOrFail(),
            Transfer::firstOrFail(),
            $loan->fresh(),
            LoanRepayment::firstOrFail(),
            TaxPayment::firstOrFail(),
        ];

        foreach ($records as $record) {
            $this->assertSame('https://example.com/receipt', $record->proof_url, $record::class);
            Storage::disk('local')->assertExists($record->proof_path);
            $this->actingAs($user)->get($record->proofFileUrl())->assertOk();
        }

        $this->actingAs($user)->get(route('finance.loans.show', $loan))->assertOk()->assertSee($records[3]->proofFileUrl());
        $this->actingAs($user)->get(route('finance.transactions'))->assertOk()->assertSee($records[1]->proofFileUrl());
    }

    public function test_finance_forms_show_proof_fields(): void
    {
        $user = $this->user();
        $loan = Loan::factory()->create();

        $pages = [
            route('finance.income.create'),
            route('finance.income.edit', IncomeTransaction::factory()->create()),
            route('finance.expenses.create'),
            route('finance.expenses.edit', ExpenseTransaction::factory()->create()),
            route('finance.transfers.create'),
            route('finance.transfers.edit', Transfer::factory()->create()),
            route('finance.loans.create'),
            route('finance.loans.edit', $loan),
            route('finance.loans.show', $loan),
            route('finance.tax-payments.create'),
            route('finance.tax-payments.edit', TaxPayment::factory()->create()),
        ];

        foreach ($pages as $page) {
            $this->actingAs($user)->get($page)
                ->assertOk()
                ->assertSee('enctype="multipart/form-data"', false)
                ->assertSee('name="proof"', false)
                ->assertSee('name="proof_url"', false);
        }
    }
}
