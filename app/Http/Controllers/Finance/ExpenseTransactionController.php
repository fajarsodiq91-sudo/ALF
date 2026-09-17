<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreExpenseTransactionRequest;
use App\Http\Requests\Finance\UpdateExpenseTransactionRequest;
use App\Models\ExpenseTransaction;

class ExpenseTransactionController extends Controller
{
    public function index()
    {
        $this->authorize('finance.manage');

        $transactions = ExpenseTransaction::query()
            ->with(['account', 'category', 'createdBy'])
            ->latest('transaction_date')
            ->paginate(20);

        return view('erp.finance.expenses.index', compact('transactions'));
    }

    public function create()
    {
        $this->authorize('finance.manage');

        return view('erp.finance.expenses.create');
    }

    public function store(StoreExpenseTransactionRequest $request)
    {
        $transaction = ExpenseTransaction::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
            'transaction_number' => 'EXP-' . date('YmdHis') . '-' . rand(1000, 9999),
        ]);

        return redirect()->route('finance.expenses')->with('status', 'Expense transaction recorded.');
    }

    public function edit(ExpenseTransaction $expense)
    {
        $this->authorize('finance.manage');

        return view('erp.finance.expenses.edit', ['transaction' => $expense]);
    }

    public function update(UpdateExpenseTransactionRequest $request, ExpenseTransaction $expense)
    {
        $expense->update($request->validated());

        return redirect()->route('finance.expenses')->with('status', 'Expense transaction updated.');
    }

    public function destroy(ExpenseTransaction $expense)
    {
        $this->authorize('finance.manage');

        $expense->delete();

        return redirect()->route('finance.expenses')->with('status', 'Expense transaction deleted.');
    }
}
