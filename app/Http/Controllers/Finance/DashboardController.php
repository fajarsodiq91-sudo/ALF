<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ExpenseTransaction;
use App\Models\IncomeTransaction;
use App\Models\Loan;
use App\Models\TaxPayment;
use App\Models\Transfer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

        $charts = [
            'cashFlow' => $this->getCashFlowTrend(),
            'expenseByCategory' => $this->getExpenseByCategory($currentYear),
            'taxPayments' => $this->getTaxPaymentsTrend(),
            'loans' => $this->getLoanSummary(),
        ];

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
            'charts',
        ));
    }

    /** Monthly income vs expense totals for the trailing 12 months. */
    private function getCashFlowTrend(): array
    {
        $dateFormat = DB::getDriverName() === 'sqlite' ? "strftime('%Y-%m', transaction_date)" : "DATE_FORMAT(transaction_date, '%Y-%m')";
        $startDate = now()->startOfMonth()->subMonths(11);

        $income = IncomeTransaction::query()
            ->where('transaction_date', '>=', $startDate)
            ->selectRaw("{$dateFormat} as month, SUM(amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $expense = ExpenseTransaction::query()
            ->where('transaction_date', '>=', $startDate)
            ->selectRaw("{$dateFormat} as month, SUM(amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $months = collect(range(0, 11))->map(fn ($i) => now()->startOfMonth()->subMonths(11 - $i)->format('Y-m'));

        return [
            'labels' => $months->map(fn ($m) => Carbon::createFromFormat('!Y-m', $m)->format('M Y'))->values(),
            'income' => $months->map(fn ($m) => (float) ($income[$m] ?? 0))->values(),
            'expense' => $months->map(fn ($m) => (float) ($expense[$m] ?? 0))->values(),
        ];
    }

    /** Expense totals grouped by category for the given year, top 6 plus an "Other" bucket. */
    private function getExpenseByCategory(int $year): array
    {
        $rows = ExpenseTransaction::query()
            ->whereYear('transaction_date', $year)
            ->with('category')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category?->name ?? 'Uncategorized',
                'total' => (float) $row->total,
            ]);

        $top = $rows->take(6);
        $otherTotal = $rows->skip(6)->sum('total');

        if ($otherTotal > 0) {
            $top->push(['category' => 'Other', 'total' => $otherTotal]);
        }

        return [
            'labels' => $top->pluck('category')->values(),
            'totals' => $top->pluck('total')->values(),
        ];
    }

    /** VAT, withholding and final tax actually paid, for the trailing 6 months. */
    private function getTaxPaymentsTrend(): array
    {
        $periods = collect(range(0, 5))->map(fn ($i) => now()->startOfMonth()->subMonths(5 - $i)->format('Y-m'));

        $vatPaid = TaxPayment::query()
            ->where('tax_type', 'vat')
            ->whereIn('period', $periods)
            ->selectRaw('period, SUM(amount) as total')
            ->groupBy('period')
            ->pluck('total', 'period');

        $whtPaid = TaxPayment::query()
            ->where('tax_type', 'withholding')
            ->whereIn('period', $periods)
            ->selectRaw('period, SUM(amount) as total')
            ->groupBy('period')
            ->pluck('total', 'period');

        $finalPaid = TaxPayment::query()
            ->where('tax_type', 'final')
            ->whereIn('period', $periods)
            ->selectRaw('period, SUM(amount) as total')
            ->groupBy('period')
            ->pluck('total', 'period');

        return [
            'labels' => $periods->map(fn ($p) => Carbon::createFromFormat('!Y-m', $p)->format('M Y'))->values(),
            'final' => $periods->map(fn ($p) => (float) ($finalPaid[$p] ?? 0))->values(),
            'vat' => $periods->map(fn ($p) => (float) ($vatPaid[$p] ?? 0))->values(),
            'withholding' => $periods->map(fn ($p) => (float) ($whtPaid[$p] ?? 0))->values(),
        ];
    }

    /** Disbursed, repaid, and outstanding totals per loan direction. */
    private function getLoanSummary(): array
    {
        $loans = Loan::query()->with('repayments')->get();

        $summary = collect(Loan::DIRECTIONS)->map(function ($label, $direction) use ($loans) {
            $group = $loans->where('direction', $direction);
            $disbursed = (float) $group->sum('amount');
            $repaid = (float) $group->sum(fn ($loan) => $loan->repaidAmount());

            return [
                'direction' => $label,
                'disbursed' => $disbursed,
                'repaid' => $repaid,
                'outstanding' => round($disbursed - $repaid, 2),
            ];
        })->values();

        return [
            'labels' => $summary->pluck('direction'),
            'disbursed' => $summary->pluck('disbursed'),
            'repaid' => $summary->pluck('repaid'),
            'outstanding' => $summary->pluck('outstanding'),
        ];
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
