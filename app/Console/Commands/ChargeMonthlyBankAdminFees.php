<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Category;
use App\Models\ExpenseTransaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ChargeMonthlyBankAdminFees extends Command
{
    protected $signature = 'finance:charge-bank-admin-fees';

    protected $description = 'Record the monthly admin fee expense for each active bank account that has one configured';

    private const PAYMENT_METHOD = 'Monthly Bank Fee';

    public function handle(): int
    {
        $recordedBy = User::whereHas('roles', fn ($query) => $query->where('name', 'Super Admin'))->value('id')
            ?? User::min('id');

        if (! $recordedBy) {
            $this->error('No user found to attribute the auto-generated expense to.');

            return self::FAILURE;
        }

        $today = Carbon::today();

        $category = Category::firstOrCreate(
            ['name' => 'Bank Charges', 'type' => 'expense'],
            ['is_active' => true, 'description' => 'Bank admin fees and account charges'],
        );

        $accounts = Account::query()
            ->where('account_type', 'bank')
            ->where('is_active', true)
            ->whereNotNull('monthly_admin_fee')
            ->where('monthly_admin_fee', '>', 0)
            ->get();

        $charged = 0;

        foreach ($accounts as $account) {
            $alreadyCharged = ExpenseTransaction::query()
                ->where('account_id', $account->id)
                ->where('payment_method', self::PAYMENT_METHOD)
                ->whereYear('transaction_date', $today->year)
                ->whereMonth('transaction_date', $today->month)
                ->exists();

            if ($alreadyCharged) {
                continue;
            }

            ExpenseTransaction::create([
                'transaction_number' => 'EXP-' . date('YmdHis') . '-' . rand(1000, 9999),
                'transaction_date' => $today,
                'account_id' => $account->id,
                'category_id' => $category->id,
                'payee' => 'Bank admin fee',
                'description' => "Monthly admin fee for {$today->format('F Y')}",
                'payment_method' => self::PAYMENT_METHOD,
                'subtotal' => $account->monthly_admin_fee,
                'tax_amount' => 0,
                'amount' => $account->monthly_admin_fee,
                'created_by' => $recordedBy,
            ]);

            $charged++;
        }

        $this->info("Monthly bank admin fees recorded for {$charged} account(s).");

        return self::SUCCESS;
    }
}
