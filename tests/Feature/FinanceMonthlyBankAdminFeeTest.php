<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ExpenseTransaction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FinanceMonthlyBankAdminFeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        User::factory()->create()->assignRole('Super Admin');
    }

    public function test_it_charges_admin_fee_for_active_bank_accounts_with_a_fee_configured(): void
    {
        $bank = Account::factory()->create([
            'account_type' => 'bank',
            'is_active' => true,
            'monthly_admin_fee' => 25_000,
        ]);

        Account::factory()->create([
            'account_type' => 'cash',
            'is_active' => true,
            'monthly_admin_fee' => 25_000,
        ]);

        Account::factory()->create([
            'account_type' => 'bank',
            'is_active' => true,
            'monthly_admin_fee' => null,
        ]);

        $this->artisan('finance:charge-bank-admin-fees')->assertExitCode(0);

        $this->assertDatabaseCount('expense_transactions', 1);

        $expense = ExpenseTransaction::first();
        $this->assertSame($bank->id, $expense->account_id);
        $this->assertSame('25000.00', $expense->amount);
        $this->assertSame('Bank Charges', $expense->category->name);
    }

    public function test_it_does_not_double_charge_within_the_same_month(): void
    {
        Account::factory()->create([
            'account_type' => 'bank',
            'is_active' => true,
            'monthly_admin_fee' => 25_000,
        ]);

        $this->artisan('finance:charge-bank-admin-fees')->assertExitCode(0);
        $this->artisan('finance:charge-bank-admin-fees')->assertExitCode(0);

        $this->assertDatabaseCount('expense_transactions', 1);
    }

    public function test_it_skips_inactive_bank_accounts(): void
    {
        Account::factory()->create([
            'account_type' => 'bank',
            'is_active' => false,
            'monthly_admin_fee' => 25_000,
        ]);

        $this->artisan('finance:charge-bank-admin-fees')->assertExitCode(0);

        $this->assertDatabaseCount('expense_transactions', 0);
    }
}
