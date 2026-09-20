<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreTaxRequest;
use App\Http\Requests\Finance\UpdateTaxRequest;
use App\Models\Tax;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaxController extends Controller
{
    public function index(): View
    {
        $taxes = Tax::orderBy('type')->orderBy('name')->get();

        return view('erp.finance.taxes.index', ['taxes' => $taxes]);
    }

    public function create(): View
    {
        $this->authorize('finance.manage');

        return view('erp.finance.taxes.create');
    }

    public function store(StoreTaxRequest $request): RedirectResponse
    {
        Tax::create([...$request->validated(), 'is_active' => $request->boolean('is_active')]);

        return redirect()->route('finance.taxes')->with('status', 'Tax created successfully.');
    }

    public function edit(Tax $tax): View
    {
        $this->authorize('finance.manage');

        return view('erp.finance.taxes.edit', ['tax' => $tax]);
    }

    public function update(UpdateTaxRequest $request, Tax $tax): RedirectResponse
    {
        $tax->update([...$request->validated(), 'is_active' => $request->boolean('is_active')]);

        return redirect()->route('finance.taxes')->with('status', 'Tax updated successfully.');
    }

    public function destroy(Tax $tax): RedirectResponse
    {
        $this->authorize('finance.manage');

        if ($tax->incomeTransactions()->exists() || $tax->expenseTransactions()->exists()) {
            return redirect()
                ->route('finance.taxes')
                ->with('error', 'This tax is used by recorded transactions and cannot be deleted. Deactivate it instead.');
        }

        $tax->delete();

        return redirect()->route('finance.taxes')->with('status', 'Tax deleted successfully.');
    }
}
