<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreLoanRepaymentRequest;
use App\Models\Loan;
use App\Models\LoanRepayment;
use Illuminate\Http\RedirectResponse;

class LoanRepaymentController extends Controller
{
    public function store(StoreLoanRepaymentRequest $request, Loan $loan): RedirectResponse
    {
        LoanRepayment::create([
            ...$request->validated(),
            'loan_id' => $loan->id,
            'created_by' => auth()->id(),
            'repayment_number' => 'LR-' . date('YmdHis') . '-' . rand(1000, 9999),
        ]);

        return redirect()->route('finance.loans.show', $loan)->with('status', 'Repayment recorded.');
    }

    public function destroy(LoanRepayment $repayment): RedirectResponse
    {
        $this->authorize('finance.manage');

        $loan = $repayment->loan;
        $repayment->delete();

        return redirect()->route('finance.loans.show', $loan)->with('status', 'Repayment deleted.');
    }
}
