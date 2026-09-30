<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Category;
use App\Models\ExpenseTransaction;
use App\Models\IncomeTransaction;
use App\Models\Tax;
use App\Models\TaxPayment;
use Illuminate\Support\Collection;
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

    /**
     * Profit and loss on an accrual footing: revenue before tax, operating costs, and
     * PPh Final accrued on income. Tax payments only settle liabilities and dividends
     * are a distribution of profit, so neither counts as an operating cost.
     */
    public function profitLoss()
    {
        $this->authorize('finance.view');

        $year = (int) request('year', now()->year);
        $dateFormat = DB::getDriverName() === 'sqlite' ? "strftime('%Y-%m', transaction_date)" : "DATE_FORMAT(transaction_date, '%Y-%m')";

        $revenue = IncomeTransaction::query()
            ->whereYear('transaction_date', $year)
            ->selectRaw("{$dateFormat} as month, SUM(subtotal) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $finalTax = IncomeTransaction::query()
            ->join('taxes', 'taxes.id', '=', 'tax_id')
            ->where('taxes.type', Tax::TYPE_FINAL)
            ->whereYear('transaction_date', $year)
            ->selectRaw("{$dateFormat} as month, SUM(tax_amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $dividendCategories = Category::where('type', 'expense')->where('name', OwnerDrawController::CATEGORIES['dividend'][0])->select('id');

        // Input VAT is a cost for a company that is not PKP, so it stays in; withholding is part of the gross cost.
        $operating = ExpenseTransaction::query()
            ->leftJoin('taxes', 'taxes.id', '=', 'expense_transactions.tax_id')
            ->whereNull('expense_transactions.tax_payment_id')
            ->whereNotIn('expense_transactions.category_id', $dividendCategories)
            ->whereYear('transaction_date', $year)
            ->selectRaw("{$dateFormat} as month, SUM(expense_transactions.subtotal + CASE WHEN taxes.type = 'vat' THEN expense_transactions.tax_amount ELSE 0 END) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $dividends = ExpenseTransaction::query()
            ->whereIn('category_id', $dividendCategories)
            ->whereYear('transaction_date', $year)
            ->selectRaw("{$dateFormat} as month, SUM(subtotal) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $data = collect()
            ->merge($revenue->keys())->merge($operating->keys())->merge($finalTax->keys())->merge($dividends->keys())
            ->unique()->sort()->values()
            ->map(function ($month) use ($revenue, $operating, $finalTax, $dividends) {
                $row = [
                    'month' => $month,
                    'revenue' => (float) ($revenue[$month] ?? 0),
                    'operating' => (float) ($operating[$month] ?? 0),
                    'final_tax' => (float) ($finalTax[$month] ?? 0),
                    'dividends' => (float) ($dividends[$month] ?? 0),
                ];
                $row['profit'] = $row['revenue'] - $row['operating'] - $row['final_tax'];

                return $row;
            });

        return view('erp.finance.reports.profit-loss', compact('data', 'year'));
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
        $finalAccrued = $collect(IncomeTransaction::class, 'final');

        $paid = fn (string $type) => TaxPayment::query()
            ->where('tax_type', $type)
            ->selectRaw('period, SUM(amount) as total')
            ->groupBy('period')
            ->pluck('total', 'period');

        $vatPaid = $paid('vat');
        $whtPaid = $paid('withholding');
        $finalPaid = $paid('final');

        $data = collect()
            ->merge($vatOut->keys())->merge($vatIn->keys())
            ->merge($whtIncome->keys())->merge($whtExpense->keys())
            ->merge($vatPaid->keys())->merge($whtPaid->keys())
            ->merge($finalAccrued->keys())->merge($finalPaid->keys())
            ->unique()->sort()->values()
            ->map(function ($month) use ($vatOut, $vatIn, $whtIncome, $whtExpense, $vatPaid, $whtPaid, $finalAccrued, $finalPaid) {
                $vatPayable = (float) ($vatOut[$month] ?? 0) - (float) ($vatIn[$month] ?? 0);
                $whtOwed = (float) ($whtExpense[$month] ?? 0);

                return [
                    'month' => $month,
                    'vat_out' => (float) ($vatOut[$month] ?? 0),
                    'vat_in' => (float) ($vatIn[$month] ?? 0),
                    'vat_payable' => $vatPayable,
                    'vat_paid' => (float) ($vatPaid[$month] ?? 0),
                    'vat_outstanding' => $vatPayable - (float) ($vatPaid[$month] ?? 0),
                    'wht_income' => (float) ($whtIncome[$month] ?? 0),
                    'wht_expense' => $whtOwed,
                    'wht_paid' => (float) ($whtPaid[$month] ?? 0),
                    'wht_outstanding' => $whtOwed - (float) ($whtPaid[$month] ?? 0),
                    'final_accrued' => (float) ($finalAccrued[$month] ?? 0),
                    'final_paid' => (float) ($finalPaid[$month] ?? 0),
                    'final_outstanding' => (float) ($finalAccrued[$month] ?? 0) - (float) ($finalPaid[$month] ?? 0),
                ];
            });

        return view('erp.finance.reports.tax-summary', compact('data'));
    }

    public function accountBalances()
    {
        $this->authorize('finance.view');

        $balances = $this->calculateAccountBalances();

        return view('erp.finance.reports.account-balances', compact('balances'));
    }

    private function calculateAccountBalances(): Collection
    {
        $accounts = Account::query()
            ->with('incomeTransactions', 'expenseTransactions', 'transfersOut', 'transfersIn', 'loans', 'loanRepayments.loan')
            ->get();

        return $accounts->map(function ($account) {
            $income = $account->incomeTransactions->sum('amount');
            $expense = $account->expenseTransactions->sum('amount');
            $outgoing = $account->transfersOut->sum('amount');
            $incoming = $account->transfersIn->sum('amount');

            $loans = $account->loanEffect();
            $balance = $account->opening_balance + $income - $expense + $incoming - $outgoing + $loans;

            return [
                'account' => $account->name,
                'initial' => $account->opening_balance,
                'income' => $income,
                'expense' => $expense,
                'transfers_in' => $incoming,
                'transfers_out' => $outgoing,
                'loans' => $loans,
                'balance' => $balance,
            ];
        });
    }
}
