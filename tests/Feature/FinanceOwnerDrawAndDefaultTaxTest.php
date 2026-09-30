<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ExpenseTransaction;
use App\Models\Setting;
use App\Models\Tax;
use App\Models\User;
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
}
