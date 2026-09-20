<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreIncomeTransactionRequest;
use App\Http\Requests\Finance\UpdateIncomeTransactionRequest;
use App\Models\IncomeTransaction;
use App\Services\TaxCalculator;

class IncomeTransactionController extends Controller
{
    public function index()
    {
        $this->authorize('finance.manage');

        $transactions = IncomeTransaction::query()
            ->with(['account', 'category', 'tax', 'createdBy'])
            ->latest('transaction_date')
            ->paginate(20);

        return view('erp.finance.income.index', compact('transactions'));
    }

    public function create()
    {
        $this->authorize('finance.manage');

        return view('erp.finance.income.create');
    }

    public function store(StoreIncomeTransactionRequest $request)
    {
        $transaction = IncomeTransaction::create([
            ...$this->withTax($request->validated()),
            'created_by' => auth()->id(),
            'transaction_number' => 'INC-' . date('YmdHis') . '-' . rand(1000, 9999),
        ]);

        return redirect()->route('finance.income')->with('status', 'Income transaction recorded.');
    }

    public function edit(IncomeTransaction $income)
    {
        $this->authorize('finance.manage');

        return view('erp.finance.income.edit', ['transaction' => $income]);
    }

    public function update(UpdateIncomeTransactionRequest $request, IncomeTransaction $income)
    {
        $income->update($this->withTax($request->validated()));

        return redirect()->route('finance.income')->with('status', 'Income transaction updated.');
    }

    public function destroy(IncomeTransaction $income)
    {
        $this->authorize('finance.manage');

        $income->delete();

        return redirect()->route('finance.income')->with('status', 'Income transaction deleted.');
    }

    private function withTax(array $data): array
    {
        return [...$data, ...TaxCalculator::apply($data['amount'], $data['tax_id'] ?? null)];
    }
}
