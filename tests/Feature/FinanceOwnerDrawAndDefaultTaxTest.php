<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\ExpenseTransaction;
use App\Models\IncomeTransaction;
use App\Models\Setting;
use App\Models\Tax;
use App\Models\User;
use App\Services\TaxCalculator;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FinanceOwnerDrawAndDefaultTaxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function finance(): User
    {
        return tap(User::factory()->create())->assignRole('Finance');
    }

    public function test_default_income_tax_is_preselected_on_the_income_form(): void
    {
        $tax = Tax::factory()->withholding(0.5)->create(['name' => 'PPh Final Test']);
        Setting::put([Tax::DEFAULT_INCOME_SETTING => $tax->id]);

        $this->actingAs($this->finance())->get(route('finance.income.create'))
            ->assertOk()
            ->assertSeeInOrder(['value="'.$tax->id.'"', 'selected']);
    }

    public function test_inactive_default_tax_is_ignored(): void
    {
        $tax = Tax::factory()->withholding(2)->create(['is_active' => false]);
        Setting::put([Tax::DEFAULT_INCOME_SETTING => $tax->id]);

        $this->assertNull(Tax::defaultForIncome());
    }

    public function test_dividend_draw_withholds_ten_percent_and_is_owed_to_tax_office(): void
    {
        $dividendTax = Tax::where('name', 'PPh Dividen (10%)')->firstOrFail();
        $account = Account::factory()->create();

        $this->actingAs($this->finance())->post(route('finance.owner-draws.store'), [
            'draw_type' => 'dividend',
            'transaction_date' => '2026-09-30',
            'account_id' => $account->id,
            'payee' => 'CEO',
            'amount' => '10000000',
            'tax_id' => $dividendTax->id,
        ])->assertRedirect(route('finance.expenses'));

        $expense = ExpenseTransaction::firstOrFail();
        $this->assertSame('10000000.00', $expense->subtotal);
        $this->assertSame('1000000.00', $expense->tax_amount);
        $this->assertSame('9000000.00', $expense->amount);
        $this->assertSame('Owner Dividends', $expense->category->name);
    }

    public function test_salary_draw_accepts_an_exact_tax_amount(): void
    {
        $tax = Tax::where('name', 'PPh 21 (5%)')->firstOrFail();

        $this->actingAs($this->finance())->post(route('finance.owner-draws.store'), [
            'draw_type' => 'salary',
            'transaction_date' => '2026-09-30',
            'account_id' => Account::factory()->create()->id,
            'payee' => 'CEO',
            'amount' => '20000000',
            'tax_id' => $tax->id,
            'tax_amount' => '750000',
        ])->assertSessionHasNoErrors();

        $expense = ExpenseTransaction::firstOrFail();
        $this->assertSame('750000.00', $expense->tax_amount);
        $this->assertSame('19250000.00', $expense->amount);
        $this->assertSame('Owner Salary', $expense->category->name);
    }

    public function test_owner_draw_requires_a_withholding_tax(): void
    {
        $vat = Tax::factory()->vat(11)->create();

        $this->actingAs($this->finance())->post(route('finance.owner-draws.store'), [
            'draw_type' => 'dividend',
            'transaction_date' => '2026-09-30',
            'account_id' => Account::factory()->create()->id,
            'payee' => 'CEO',
            'amount' => '1000',
            'tax_id' => $vat->id,
        ])->assertSessionHasErrors('tax_id');

        $this->assertSame(0, ExpenseTransaction::count());
    }

    public function test_final_tax_is_accrued_and_income_is_received_in_full(): void
    {
        $final = Tax::where('name', 'PPh Final UMKM (0,5%)')->firstOrFail();
        $this->assertSame(Tax::TYPE_FINAL, $final->type);

        $result = TaxCalculator::apply(10000000, $final->id);

        $this->assertSame('50000.00', $result['tax_amount']);
        $this->assertSame('10000000.00', $result['amount']);
    }

    public function test_final_tax_cannot_be_applied_to_an_expense(): void
    {
        $final = Tax::where('name', 'PPh Final UMKM (0,5%)')->firstOrFail();

        $this->actingAs($this->finance())->post(route('finance.expenses.store'), [
            'transaction_date' => '2026-09-30',
            'amount' => '1000',
            'tax_id' => $final->id,
            'account_id' => Account::factory()->create()->id,
            'category_id' => Category::factory()->create(['type' => 'expense'])->id,
            'payee' => 'Vendor',
            'payment_method' => 'Cash',
        ])->assertSessionHasErrors('tax_id');
    }

    public function test_tax_summary_shows_final_tax_accrued_paid_and_outstanding(): void
    {
        $final = Tax::where('name', 'PPh Final UMKM (0,5%)')->firstOrFail();
        $account = Account::factory()->create();
        $user = $this->finance();

        $this->actingAs($user)->post(route('finance.income.store'), [
            'transaction_date' => '2026-09-10',
            'amount' => '10000000',
            'tax_id' => $final->id,
            'account_id' => $account->id,
            'category_id' => Category::factory()->create(['type' => 'income'])->id,
            'source' => 'Client',
            'payment_method' => 'Bank Transfer',
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->post(route('finance.tax-payments.store'), [
            'payment_date' => '2026-10-15',
            'account_id' => $account->id,
            'tax_type' => 'final',
            'period' => '2026-09',
            'amount' => '20000',
        ])->assertSessionHasNoErrors();

        $this->get(route('finance.reports.tax-summary'))
            ->assertOk()
            ->assertSee('Rp 50.000,00')
            ->assertSee('Rp 20.000,00')
            ->assertSee('Rp 30.000,00');
    }

    public function test_profit_loss_excludes_dividends_and_tax_payments_from_costs(): void
    {
        $final = Tax::where('name', 'PPh Final UMKM (0,5%)')->firstOrFail();
        $account = Account::factory()->create();
        $incomeCategory = Category::factory()->create(['type' => 'income']);
        $expenseCategory = Category::factory()->create(['type' => 'expense']);
        $user = $this->finance();

        IncomeTransaction::factory()->create([
            'transaction_date' => '2026-09-10', 'account_id' => $account->id, 'category_id' => $incomeCategory->id,
            'subtotal' => 10000000, 'tax_id' => $final->id, 'tax_rate' => 0.5, 'tax_amount' => 50000, 'amount' => 10000000,
        ]);
        ExpenseTransaction::factory()->create([
            'transaction_date' => '2026-09-11', 'account_id' => $account->id, 'category_id' => $expenseCategory->id,
            'subtotal' => 2000000, 'tax_amount' => 0, 'amount' => 2000000,
        ]);

        $this->actingAs($user)->post(route('finance.owner-draws.store'), [
            'draw_type' => 'dividend', 'transaction_date' => '2026-09-30', 'account_id' => $account->id,
            'payee' => 'CEO', 'amount' => '3000000', 'tax_id' => Tax::where('name', 'PPh Dividen (10%)')->value('id'),
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->post(route('finance.tax-payments.store'), [
            'payment_date' => '2026-10-15', 'account_id' => $account->id, 'tax_type' => 'final',
            'period' => '2026-09', 'amount' => '50000',
        ]);

        $row = $this->get(route('finance.reports.profit-loss', ['year' => 2026]))
            ->assertOk()->viewData('data')->firstWhere('month', '2026-09');

        $this->assertSame(10000000.0, $row['revenue']);
        $this->assertSame(2000000.0, $row['operating']);
        $this->assertSame(50000.0, $row['final_tax']);
        $this->assertSame(7950000.0, $row['profit']);
        $this->assertSame(3000000.0, $row['dividends']);
    }
}
