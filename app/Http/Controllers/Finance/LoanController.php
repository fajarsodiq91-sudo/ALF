<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreLoanRequest;
use App\Http\Requests\Finance\UpdateLoanRequest;
use App\Models\Loan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LoanController extends Controller
{
    public function index(): View
    {
        $this->authorize('finance.view');

        $loans = Loan::query()
            ->with(['account', 'repayments', 'createdBy'])
            ->latest('loan_date')
            ->get();

        $owedToOwner = $loans->where('direction', Loan::COMPANY_BORROWS)->sum(fn (Loan $l) => $l->outstandingAmount());
        $owedByOwner = $loans->where('direction', Loan::OWNER_BORROWS)->sum(fn (Loan $l) => $l->outstandingAmount());

        return view('erp.finance.loans.index', compact('loans', 'owedToOwner', 'owedByOwner'));
    }

    public function create(): View
    {
        $this->authorize('finance.manage');

        return view('erp.finance.loans.create');
    }

    public function store(StoreLoanRequest $request): RedirectResponse
    {
        $loan = Loan::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
            'loan_number' => 'LN-' . date('YmdHis') . '-' . rand(1000, 9999),
        ]);

        return redirect()->route('finance.loans.show', $loan)->with('status', 'Loan recorded.');
    }

    public function show(Loan $loan): View
    {
        $this->authorize('finance.view');

        $loan->load(['account', 'repayments.account', 'repayments.createdBy', 'createdBy']);

        return view('erp.finance.loans.show', compact('loan'));
    }

    public function edit(Loan $loan): View
    {
        $this->authorize('finance.manage');

        return view('erp.finance.loans.edit', compact('loan'));
    }

    public function update(UpdateLoanRequest $request, Loan $loan): RedirectResponse
    {
        $loan->update($request->validated());

        return redirect()->route('finance.loans.show', $loan)->with('status', 'Loan updated.');
    }

    public function destroy(Loan $loan): RedirectResponse
    {
        $this->authorize('finance.manage');

        if ($loan->repayments()->exists()) {
            return redirect()
                ->route('finance.loans.show', $loan)
                ->with('error', 'This loan has repayments recorded. Delete the repayments first.');
        }

        $loan->delete();

        return redirect()->route('finance.loans')->with('status', 'Loan deleted.');
    }
}
