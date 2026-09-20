<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ExpenseTransaction;
use App\Models\IncomeTransaction;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\Transfer;
use Illuminate\Support\Collection;

class TransactionLedgerController extends Controller
{
    public function index()
    {
        $this->authorize('finance.view');

        $income = IncomeTransaction::query()
            ->with(['account', 'category', 'createdBy'])
            ->get()
            ->map(fn ($t) => [
                'type' => 'income',
                'date' => $t->transaction_date,
                'number' => $t->transaction_number,
                'account' => $t->account?->name,
                'category' => $t->category?->name,
                'description' => $t->source,
                'amount' => $t->amount,
                'created_by' => $t->createdBy?->name,
            ]);

        $expenses = ExpenseTransaction::query()
            ->with(['account', 'category', 'createdBy'])
            ->get()
            ->map(fn ($t) => [
                'type' => 'expense',
                'date' => $t->transaction_date,
                'number' => $t->transaction_number,
                'account' => $t->account?->name,
                'category' => $t->category?->name,
                'description' => $t->payee,
                'amount' => $t->amount,
                'created_by' => $t->createdBy?->name,
            ]);

        $transfers = Transfer::query()
            ->with(['fromAccount', 'toAccount', 'createdBy'])
            ->get()
            ->map(fn ($t) => [
                'type' => 'transfer',
                'date' => $t->transfer_date,
                'number' => $t->transfer_number,
                'from_account' => $t->fromAccount?->name,
                'to_account' => $t->toAccount?->name,
                'description' => $t->description,
                'amount' => $t->amount,
                'created_by' => $t->createdBy?->name,
            ]);

        $loans = Loan::query()
            ->with(['account', 'createdBy'])
            ->get()
            ->map(fn ($t) => [
                'type' => 'loan',
                'date' => $t->loan_date,
                'number' => $t->loan_number,
                'account' => $t->account?->name,
                'description' => Loan::DIRECTIONS[$t->direction] . ' — ' . $t->party_name,
                'amount' => $t->amount,
                'flow' => $t->disbursementSign(),
                'created_by' => $t->createdBy?->name,
            ]);

        $repayments = LoanRepayment::query()
            ->with(['loan', 'account', 'createdBy'])
            ->get()
            ->map(fn ($t) => [
                'type' => 'repayment',
                'date' => $t->repayment_date,
                'number' => $t->repayment_number,
                'account' => $t->account?->name,
                'description' => 'Repayment of ' . $t->loan->loan_number . ' — ' . $t->loan->party_name,
                'amount' => $t->amount,
                'flow' => -$t->loan->disbursementSign(),
                'created_by' => $t->createdBy?->name,
            ]);

        $transactions = collect()
            ->merge($income)
            ->merge($expenses)
            ->merge($transfers)
            ->merge($loans)
            ->merge($repayments)
            ->sortByDesc('date')
            ->values();

        return view('erp.finance.ledger.index', compact('transactions'));
    }
}
