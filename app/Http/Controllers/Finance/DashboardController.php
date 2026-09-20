<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ExpenseTransaction;
use App\Models\IncomeTransaction;
use App\Models\Transfer;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $this->authorize('finance.view');

        $currentYear = now()->year;
        $currentMonth = now()->month;

        // Current month metrics
        $monthlyIncome = IncomeTransaction::whereYear('transaction_date', $currentYear)
            ->whereMonth('transaction_date', $currentMonth)
            ->sum('amount');

        $monthlyExpense = ExpenseTransaction::whereYear('transaction_date', $currentYear)
            ->whereMonth('transaction_date', $currentMonth)
            ->sum('amount');

        $monthlyNet = $monthlyIncome - $monthlyExpense;

        // Year-to-date metrics
        $yearIncome = IncomeTransaction::whereYear('transaction_date', $currentYear)->sum('amount');
        $yearExpense = ExpenseTransaction::whereYear('transaction_date', $currentYear)->sum('amount');
        $yearNet = $yearIncome - $yearExpense;

        // All-time metrics
        $totalIncome = IncomeTransaction::sum('amount');
        $totalExpense = ExpenseTransaction::sum('amount');
        $totalNet = $totalIncome - $totalExpense;

        // Account balances
        $accountBalances = $this->getAccountBalances();
        $totalBalance = $accountBalances->sum('balance');

        // Recent transactions
        $recentIncome = IncomeTransaction::query()
            ->with(['account', 'category', 'createdBy'])
            ->latest('transaction_date')
            ->limit(5)
            ->get()
            ->map(fn ($t) => [
                'type' => 'income',
                'date' => $t->transaction_date,
                'number' => $t->transaction_number,
                'description' => $t->source,
                'amount' => $t->amount,
                'account' => $t->account?->name,
            ]);

        $recentExpense = ExpenseTransaction::query()
            ->with(['account', 'category', 'createdBy'])
            ->latest('transaction_date')
            ->limit(5)
            ->get()
            ->map(fn ($t) => [
                'type' => 'expense',
                'date' => $t->transaction_date,
                'number' => $t->transaction_number,
                'description' => $t->payee,
                'amount' => $t->amount,
                'account' => $t->account?->name,
            ]);

        $recentTransfer = Transfer::query()
            ->with(['fromAccount', 'toAccount', 'createdBy'])
            ->latest('transfer_date')
            ->limit(5)
            ->get()
            ->map(fn ($t) => [
                'type' => 'transfer',
                'date' => $t->transfer_date,
                'number' => $t->transfer_number,
                'description' => "{$t->fromAccount?->name} → {$t->toAccount?->name}",
                'amount' => $t->amount,
                'account' => null,
            ]);

        $recentTransactions = collect()
            ->merge($recentIncome)
            ->merge($recentExpense)
            ->merge($recentTransfer)
            ->sortByDesc('date')
            ->take(10)
            ->values();

        return view('erp.finance.dashboard', compact(
            'monthlyIncome',
            'monthlyExpense',
            'monthlyNet',
            'yearIncome',
            'yearExpense',
            'yearNet',
            'totalIncome',
            'totalExpense',
            'totalNet',
            'totalBalance',
            'accountBalances',
            'recentTransactions',
        ));
    }

    private function getAccountBalances()
    {
        return Account::query()
            ->with('incomeTransactions', 'expenseTransactions', 'transfersOut', 'transfersIn', 'loans', 'loanRepayments.loan')
            ->get()
            ->map(function ($account) {
                $income = $account->incomeTransactions->sum('amount');
                $expense = $account->expenseTransactions->sum('amount');
                $outgoing = $account->transfersOut->sum('amount');
                $incoming = $account->transfersIn->sum('amount');

                $loans = $account->loanEffect();
                $balance = $account->opening_balance + $income - $expense + $incoming - $outgoing + $loans;

                return [
                    'name' => $account->name,
                    'opening_balance' => $account->opening_balance,
                    'balance' => $balance,
                ];
            })
            ->sortByDesc('balance');
    }
}
