<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Category;
use App\Models\ExpenseTransaction;
use App\Models\IncomeTransaction;
use App\Models\Transfer;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $this->authorize('finance.view');

        $incomeTotal = IncomeTransaction::sum('amount');
        $expenseTotal = ExpenseTransaction::sum('amount');
        $netIncome = $incomeTotal - $expenseTotal;

        $accountBalances = $this->calculateAccountBalances();

        return view('erp.finance.reports.index', compact(
            'incomeTotal',
            'expenseTotal',
            'netIncome',
            'accountBalances',
        ));
    }

    public function incomeByCategory()
    {
        $this->authorize('finance.view');

        $data = IncomeTransaction::query()
            ->with('category')
            ->selectRaw('category_id, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category_id')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category?->name ?? 'Uncategorized',
                'total' => $row->total,
                'count' => $row->count,
            ]);

        return view('erp.finance.reports.income-by-category', compact('data'));
    }

    public function expenseByCategory()
    {
        $this->authorize('finance.view');

        $data = ExpenseTransaction::query()
            ->with('category')
            ->selectRaw('category_id, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category_id')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category?->name ?? 'Uncategorized',
                'total' => $row->total,
                'count' => $row->count,
            ]);

        return view('erp.finance.reports.expense-by-category', compact('data'));
    }

    public function monthlyFlow()
    {
        $this->authorize('finance.view');

        $dateFormat = DB::getDriverName() === 'sqlite' ? "strftime('%Y-%m', transaction_date)" : "DATE_FORMAT(transaction_date, '%Y-%m')";

        $income = IncomeTransaction::query()
            ->selectRaw("{$dateFormat} as month, SUM(amount) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month');

        $expense = ExpenseTransaction::query()
            ->selectRaw("{$dateFormat} as month, SUM(amount) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month');

        $months = collect()
            ->merge($income->keys())
            ->merge($expense->keys())
            ->unique()
            ->sort()
            ->values();

        $data = $months->map(fn ($month) => [
            'month' => $month,
            'income' => $income[$month] ?? 0,
            'expense' => $expense[$month] ?? 0,
            'net' => ($income[$month] ?? 0) - ($expense[$month] ?? 0),
        ]);

        return view('erp.finance.reports.monthly-flow', compact('data'));
    }

    public function taxSummary()
    {
        $this->authorize('finance.view');

        $dateFormat = DB::getDriverName() === 'sqlite' ? "strftime('%Y-%m', transaction_date)" : "DATE_FORMAT(transaction_date, '%Y-%m')";

        $collect = fn (string $model, string $type) => $model::query()
            ->join('taxes', 'taxes.id', '=', 'tax_id')
            ->where('taxes.type', $type)
            ->selectRaw("{$dateFormat} as month, SUM(tax_amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $vatOut = $collect(IncomeTransaction::class, 'vat');
        $vatIn = $collect(ExpenseTransaction::class, 'vat');
        $whtIncome = $collect(IncomeTransaction::class, 'withholding');
        $whtExpense = $collect(ExpenseTransaction::class, 'withholding');

        $data = collect()
            ->merge($vatOut->keys())->merge($vatIn->keys())
            ->merge($whtIncome->keys())->merge($whtExpense->keys())
            ->unique()->sort()->values()
            ->map(fn ($month) => [
                'month' => $month,
                'vat_out' => (float) ($vatOut[$month] ?? 0),
                'vat_in' => (float) ($vatIn[$month] ?? 0),
                'vat_payable' => (float) ($vatOut[$month] ?? 0) - (float) ($vatIn[$month] ?? 0),
                'wht_income' => (float) ($whtIncome[$month] ?? 0),
                'wht_expense' => (float) ($whtExpense[$month] ?? 0),
            ]);

        return view('erp.finance.reports.tax-summary', compact('data'));
    }

    public function accountBalances()
    {
        $this->authorize('finance.view');

        $balances = $this->calculateAccountBalances();

        return view('erp.finance.reports.account-balances', compact('balances'));
    }

    private function calculateAccountBalances(): \Illuminate\Support\Collection
    {
        $accounts = Account::query()
            ->with('incomeTransactions', 'expenseTransactions', 'transfersOut', 'transfersIn')
            ->get();

        return $accounts->map(function ($account) {
            $income = $account->incomeTransactions->sum('amount');
            $expense = $account->expenseTransactions->sum('amount');
            $outgoing = $account->transfersOut->sum('amount');
            $incoming = $account->transfersIn->sum('amount');

            $balance = $account->opening_balance + $income - $expense + $incoming - $outgoing;

            return [
                'account' => $account->name,
                'initial' => $account->opening_balance,
                'income' => $income,
                'expense' => $expense,
                'transfers_in' => $incoming,
                'transfers_out' => $outgoing,
                'balance' => $balance,
            ];
        });
    }
}
