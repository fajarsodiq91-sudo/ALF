<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreOwnerDrawRequest;
use App\Models\Category;
use App\Models\ExpenseTransaction;
use App\Services\TaxCalculator;
use App\Services\TransactionProof;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Money the owner takes out of the company. The owner receives the gross amount
 * less withholding tax; the withheld tax is owed to the tax office and is settled
 * later through Tax Payments, so only the net amount leaves the account now.
 */
class OwnerDrawController extends Controller
{
    /** draw type => [expense category name, description] */
    public const CATEGORIES = [
        'dividend' => ['Owner Dividends', 'Profit distributed to the owner. An equity distribution, not an operating cost.'],
        'salary' => ['Owner Salary', 'Salary paid to the owner. An operating expense.'],
    ];

    public function create(): View
    {
        $this->authorize('finance.manage');

        return view('erp.finance.owner-draws.create');
    }

    public function store(StoreOwnerDrawRequest $request): RedirectResponse
    {
        [$categoryName, $categoryDescription] = self::CATEGORIES[$request->input('draw_type')];

        $category = Category::firstOrCreate(
            ['name' => $categoryName, 'type' => 'expense'],
            ['is_active' => true, 'description' => $categoryDescription],
        );

        $tax = TaxCalculator::apply($request->input('amount'), $request->integer('tax_id'));

        // The rate-based tax can be overridden with the exact figure (e.g. progressive PPh 21).
        if ($request->filled('tax_amount')) {
            $taxAmount = round((float) $request->input('tax_amount'), 2);
            $tax['tax_amount'] = number_format($taxAmount, 2, '.', '');
            $tax['amount'] = number_format((float) $tax['subtotal'] - $taxAmount, 2, '.', '');
        }

        ExpenseTransaction::create([
            ...$tax,
            ...TransactionProof::attributes($request, new ExpenseTransaction),
            'transaction_number' => 'EXP-'.date('YmdHis').'-'.rand(1000, 9999),
            'transaction_date' => $request->input('transaction_date'),
            'account_id' => $request->input('account_id'),
            'category_id' => $category->id,
            'payee' => $request->input('payee'),
            'description' => $categoryName.' — '.$request->input('payee'),
            'payment_method' => 'Owner Draw',
            'notes' => $request->input('notes'),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('finance.expenses')->with('status', 'Owner draw recorded. The withheld tax is owed until you record its payment under Tax Payments.');
    }
}
