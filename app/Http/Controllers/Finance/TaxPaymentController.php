<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreTaxPaymentRequest;
use App\Http\Requests\Finance\UpdateTaxPaymentRequest;
use App\Models\Category;
use App\Models\ExpenseTransaction;
use App\Models\TaxPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TaxPaymentController extends Controller
{
    public function index(): View
    {
        $this->authorize('finance.view');

        $payments = TaxPayment::query()
            ->with(['account', 'createdBy'])
            ->latest('payment_date')
            ->paginate(20);

        return view('erp.finance.tax-payments.index', compact('payments'));
    }

    public function create(): View
    {
        $this->authorize('finance.manage');

        return view('erp.finance.tax-payments.create');
    }

    public function store(StoreTaxPaymentRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $payment = TaxPayment::create([
                ...$request->validated(),
                'created_by' => auth()->id(),
                'payment_number' => 'TAX-' . date('YmdHis') . '-' . rand(1000, 9999),
            ]);

            $this->syncExpense($payment);
        });

        return redirect()->route('finance.tax-payments')->with('status', 'Tax payment recorded.');
    }

    public function edit(TaxPayment $taxPayment): View
    {
        $this->authorize('finance.manage');

        return view('erp.finance.tax-payments.edit', compact('taxPayment'));
    }

    public function update(UpdateTaxPaymentRequest $request, TaxPayment $taxPayment): RedirectResponse
    {
        DB::transaction(function () use ($request, $taxPayment) {
            $taxPayment->update($request->validated());

            $this->syncExpense($taxPayment);
        });

        return redirect()->route('finance.tax-payments')->with('status', 'Tax payment updated.');
    }

    public function destroy(TaxPayment $taxPayment): RedirectResponse
    {
        $this->authorize('finance.manage');

        $taxPayment->delete();

        return redirect()->route('finance.tax-payments')->with('status', 'Tax payment deleted.');
    }

    /** Money paid to the tax office leaves the account, so it is mirrored as an expense to keep balances and reports correct. */
    private function syncExpense(TaxPayment $payment): void
    {
        $category = Category::firstOrCreate(
            ['name' => 'Tax Payments', 'type' => 'expense'],
            ['is_active' => true, 'description' => 'Tax remitted to the tax office (PPN, PPh)'],
        );

        $label = $payment->tax_type === 'vat' ? 'PPN' : 'PPh';

        $attributes = [
            'transaction_date' => $payment->payment_date,
            'account_id' => $payment->account_id,
            'category_id' => $category->id,
            'payee' => 'Tax office',
            'description' => "{$label} payment for {$payment->period} ({$payment->payment_number})",
            'payment_method' => 'Tax Payment',
            'subtotal' => $payment->amount,
            'tax_amount' => 0,
            'amount' => $payment->amount,
        ];

        if ($payment->expense) {
            $payment->expense->update($attributes);

            return;
        }

        ExpenseTransaction::create([
            ...$attributes,
            'tax_payment_id' => $payment->id,
            'transaction_number' => 'EXP-' . date('YmdHis') . '-' . rand(1000, 9999),
            'created_by' => auth()->id(),
        ]);
    }
}
