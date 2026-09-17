<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreAccountRequest;
use App\Http\Requests\Finance\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(): View
    {
        $accounts = Account::orderBy('name')->get();

        return view('erp.finance.accounts.index', ['accounts' => $accounts]);
    }

    public function create(): View
    {
        $this->authorize('finance.manage');

        return view('erp.finance.accounts.create');
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        Account::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('finance.accounts')
            ->with('status', 'Account created successfully.');
    }

    public function edit(Account $account): View
    {
        $this->authorize('finance.manage');

        return view('erp.finance.accounts.edit', ['account' => $account]);
    }

    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $account->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('finance.accounts')
            ->with('status', 'Account updated successfully.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        $this->authorize('finance.manage');

        $hasTransactions = $account->incomeTransactions()->exists()
            || $account->expenseTransactions()->exists()
            || $account->transfersIn()->exists()
            || $account->transfersOut()->exists();

        if ($hasTransactions) {
            return redirect()
                ->route('finance.accounts')
                ->with('error', 'This account has transactions recorded against it and cannot be deleted. Deactivate it instead.');
        }

        $account->delete();

        return redirect()
            ->route('finance.accounts')
            ->with('status', 'Account deleted successfully.');
    }
}
