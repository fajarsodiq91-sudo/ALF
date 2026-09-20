<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\ExpenseTransaction;
use App\Models\IncomeTransaction;
use App\Models\Tax;
use App\Models\TaxPayment;
use App\Models\User;
use App\Services\TaxCalculator;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FinanceTaxTest extends TestCase
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

    public function test_calculator_adds_vat_and_deducts_withholding(): void
    {
        $vat = Tax::factory()->vat(11)->create();
        $pph = Tax::factory()->withholding(2)->create();

        $this->assertSame('1110000.00', TaxCalculator::apply(1_000_000, $vat->id)['amount']);
        $this->assertSame('110000.00', TaxCalculator::apply(1_000_000, $vat->id)['tax_amount']);
        $this->assertSame('980000.00', TaxCalculator::apply(1_000_000, $pph->id)['amount']);
        $this->assertSame('1000000.00', TaxCalculator::apply(1_000_000, null)['amount']);
    }

    public function test_income_with_vat_stores_tax_and_total(): void
    {
        $tax = Tax::factory()->vat(11)->create();
        $account = Account::factory()->create();
        $category = Category::factory()->income()->create();

        $this->actingAs($this->user())->post(route('finance.income.store'), [
            'transaction_date' => '2026-09-20',
            'amount' => 1_000_000,
            'tax_id' => $tax->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'source' => 'PT Maju',
            'payment_method' => 'Bank Transfer',
        ])->assertRedirect(route('finance.income'));

        $this->assertDatabaseHas('income_transactions', [
            'subtotal' => 1_000_000,
            'tax_id' => $tax->id,
            'tax_rate' => 11,
            'tax_amount' => 110_000,
            'amount' => 1_110_000,
        ]);
    }

    public function test_expense_with_withholding_deducts_from_total_and_update_recalculates(): void
    {
        $pph = Tax::factory()->withholding(2)->create();
        $expense = ExpenseTransaction::factory()->create();
        $payload = [
            'transaction_date' => '2026-09-20',
            'amount' => 500_000,
            'tax_id' => $pph->id,
            'account_id' => $expense->account_id,
            'category_id' => $expense->category_id,
            'payee' => 'Vendor',
            'payment_method' => 'Cash',
        ];

        $this->actingAs($this->user())->put(route('finance.expenses.update', $expense), $payload)
            ->assertRedirect(route('finance.expenses'));

        $expense->refresh();
        $this->assertEquals(10_000, $expense->tax_amount);
        $this->assertEquals(490_000, $expense->amount);

        $this->actingAs($this->user())->put(route('finance.expenses.update', $expense), [...$payload, 'tax_id' => null]);
        $this->assertEquals(500_000, $expense->fresh()->amount);
        $this->assertNull($expense->fresh()->tax_id);
    }

    public function test_tax_summary_report_aggregates_vat(): void
    {
        $vat = Tax::factory()->vat(10)->create();
        IncomeTransaction::factory()->create(['transaction_date' => '2026-09-05', 'tax_id' => $vat->id, 'tax_amount' => 100, 'subtotal' => 1000, 'amount' => 1100]);
        ExpenseTransaction::factory()->create(['transaction_date' => '2026-09-06', 'tax_id' => $vat->id, 'tax_amount' => 30, 'subtotal' => 300, 'amount' => 330]);

        $this->actingAs($this->user())->get(route('finance.reports.tax-summary'))
            ->assertOk()
            ->assertSee('2026-09')
            ->assertSee('70,00');
    }

    public function test_tax_crud_and_delete_guard(): void
    {
        $finance = $this->user();

        $this->actingAs($finance)->post(route('finance.taxes.store'), [
            'name' => 'PPN 12%', 'type' => 'vat', 'rate' => 12, 'is_active' => 1,
        ])->assertRedirect(route('finance.taxes'));
        $tax = Tax::where('name', 'PPN 12%')->firstOrFail();

        IncomeTransaction::factory()->create(['tax_id' => $tax->id]);
        $this->actingAs($finance)->delete(route('finance.taxes.destroy', $tax))->assertSessionHas('error');
        $this->assertModelExists($tax);
    }

    public function test_viewer_cannot_manage_taxes(): void
    {
        $viewer = $this->user('Viewer');

        $this->actingAs($viewer)->get(route('finance.taxes'))->assertOk();
        $this->actingAs($viewer)->post(route('finance.taxes.store'), [
            'name' => 'X', 'type' => 'vat', 'rate' => 5,
        ])->assertForbidden();
    }

    public function test_tax_payment_reduces_balance_and_is_recorded_as_expense(): void
    {
        $account = Account::factory()->create(['opening_balance' => 5_000_000]);

        $this->actingAs($this->user())->post(route('finance.tax-payments.store'), [
            'tax_type' => 'vat', 'period' => '2026-08', 'payment_date' => '2026-09-10',
            'account_id' => $account->id, 'amount' => 800_000, 'reference' => 'NTPN123',
        ])->assertRedirect(route('finance.tax-payments'));

        $payment = TaxPayment::firstOrFail();
        $this->assertEquals(800_000, $payment->expense->amount);
        $this->assertSame('Tax Payments', $payment->expense->category->name);
        $this->assertEquals(4_200_000, $account->currentBalance());
    }

    public function test_tax_payment_update_and_delete_keep_expense_in_sync(): void
    {
        $account = Account::factory()->create(['opening_balance' => 1_000_000]);
        $payment = TaxPayment::factory()->create(['account_id' => $account->id, 'amount' => 100_000]);
        $user = $this->user();
        $this->actingAs($user)->put(route('finance.tax-payments.update', $payment), [
            'tax_type' => 'vat', 'period' => '2026-08', 'payment_date' => '2026-09-10',
            'account_id' => $account->id, 'amount' => 250_000,
        ])->assertRedirect(route('finance.tax-payments'));

        $this->assertSame(1, ExpenseTransaction::count());
        $this->assertEquals(250_000, $payment->fresh()->expense->amount);

        $this->actingAs($user)->delete(route('finance.tax-payments.destroy', $payment));
        $this->assertSame(0, ExpenseTransaction::count());
    }

    public function test_tax_summary_shows_paid_and_outstanding(): void
    {
        $vat = Tax::factory()->vat(10)->create();
        IncomeTransaction::factory()->create(['transaction_date' => '2026-08-05', 'tax_id' => $vat->id, 'tax_amount' => 1_000, 'subtotal' => 10_000, 'amount' => 11_000]);
        TaxPayment::factory()->create(['tax_type' => 'vat', 'period' => '2026-08', 'amount' => 400]);

        $response = $this->actingAs($this->user())->get(route('finance.reports.tax-summary'))->assertOk();
        $data = $response->viewData('data')->firstWhere('month', '2026-08');
        $this->assertEquals(1_000, $data['vat_payable']);
        $this->assertEquals(400, $data['vat_paid']);
        $this->assertEquals(600, $data['vat_outstanding']);
    }

    public function test_tax_payment_validation_and_permissions(): void
    {
        $account = Account::factory()->create();

        $this->actingAs($this->user())->post(route('finance.tax-payments.store'), [
            'tax_type' => 'vat', 'period' => '2026-13', 'payment_date' => '2026-09-10',
            'account_id' => $account->id, 'amount' => 1,
        ])->assertSessionHasErrors('period');

        $viewer = $this->user('Viewer');
        $this->actingAs($viewer)->get(route('finance.tax-payments'))->assertOk();
        $this->actingAs($viewer)->get(route('finance.tax-payments.create'))->assertForbidden();
    }
}
