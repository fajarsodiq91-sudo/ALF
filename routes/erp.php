<?php

use App\Http\Controllers\Finance\AccountController;
use App\Http\Controllers\Finance\CategoryController;
use App\Http\Controllers\Finance\DashboardController;
use App\Http\Controllers\Finance\IncomeTransactionController;
use App\Http\Controllers\Finance\LoanController;
use App\Http\Controllers\Finance\LoanRepaymentController;
use App\Http\Controllers\Finance\ExpenseTransactionController;
use App\Http\Controllers\Finance\ReportController;
use App\Http\Controllers\Finance\TaxController;
use App\Http\Controllers\Finance\TaxPaymentController;
use App\Http\Controllers\Finance\TransactionLedgerController;
use App\Http\Controllers\Finance\TransferTransactionController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\SystemSettingController;
use App\Http\Controllers\Settings\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/erp', '/erp/dashboard');

Route::get('/erp/dashboard', function () {
    return view('erp.dashboard');
})->middleware(['auth', 'verified', 'permission:access-erp'])->name('dashboard');

Route::middleware(['auth', 'verified'])->prefix('erp')->group(function () {

    // Finance — nav structure only for now; each page becomes real in its own phase.
    Route::redirect('/finance', '/finance/dashboard');

    Route::middleware('permission:finance.view')->prefix('finance')->name('finance.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('accounts', AccountController::class)->except(['show'])->names([
            'index' => 'accounts',
            'create' => 'accounts.create',
            'store' => 'accounts.store',
            'edit' => 'accounts.edit',
            'update' => 'accounts.update',
            'destroy' => 'accounts.destroy',
        ]);

        Route::resource('categories', CategoryController::class)->except(['show'])->names([
            'index' => 'categories',
            'create' => 'categories.create',
            'store' => 'categories.store',
            'edit' => 'categories.edit',
            'update' => 'categories.update',
            'destroy' => 'categories.destroy',
        ]);

        Route::resource('taxes', TaxController::class)->except(['show'])->names([
            'index' => 'taxes',
            'create' => 'taxes.create',
            'store' => 'taxes.store',
            'edit' => 'taxes.edit',
            'update' => 'taxes.update',
            'destroy' => 'taxes.destroy',
        ]);

        Route::resource('tax-payments', TaxPaymentController::class)->except(['show'])->parameters(['tax-payments' => 'taxPayment'])->names([
            'index' => 'tax-payments',
            'create' => 'tax-payments.create',
            'store' => 'tax-payments.store',
            'edit' => 'tax-payments.edit',
            'update' => 'tax-payments.update',
            'destroy' => 'tax-payments.destroy',
        ]);

        Route::resource('income', IncomeTransactionController::class)->except(['show'])->names([
            'index' => 'income',
            'create' => 'income.create',
            'store' => 'income.store',
            'edit' => 'income.edit',
            'update' => 'income.update',
            'destroy' => 'income.destroy',
        ]);

        Route::resource('expenses', ExpenseTransactionController::class)->except(['show'])->names([
            'index' => 'expenses',
            'create' => 'expenses.create',
            'store' => 'expenses.store',
            'edit' => 'expenses.edit',
            'update' => 'expenses.update',
            'destroy' => 'expenses.destroy',
        ]);

        Route::resource('transfers', TransferTransactionController::class)->except(['show'])->names([
            'index' => 'transfers',
            'create' => 'transfers.create',
            'store' => 'transfers.store',
            'edit' => 'transfers.edit',
            'update' => 'transfers.update',
            'destroy' => 'transfers.destroy',
        ]);

        Route::resource('loans', LoanController::class)->names([
            'index' => 'loans',
            'create' => 'loans.create',
            'store' => 'loans.store',
            'show' => 'loans.show',
            'edit' => 'loans.edit',
            'update' => 'loans.update',
            'destroy' => 'loans.destroy',
        ]);
        Route::post('loans/{loan}/repayments', [LoanRepaymentController::class, 'store'])->name('loans.repayments.store');
        Route::delete('loan-repayments/{repayment}', [LoanRepaymentController::class, 'destroy'])->name('loans.repayments.destroy');

        Route::get('/transactions', [TransactionLedgerController::class, 'index'])->name('transactions');

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/income-by-category', [ReportController::class, 'incomeByCategory'])->name('income-by-category');
            Route::get('/expense-by-category', [ReportController::class, 'expenseByCategory'])->name('expense-by-category');
            Route::get('/monthly-flow', [ReportController::class, 'monthlyFlow'])->name('monthly-flow');
            Route::get('/tax-summary', [ReportController::class, 'taxSummary'])->name('tax-summary');
            Route::get('/account-balances', [ReportController::class, 'accountBalances'])->name('account-balances');
        });
    });

    // Planned modules — placeholder pages only, no functionality yet.
    Route::middleware('permission:access-erp')->group(function () {
        Route::view('/sales', 'erp.coming-soon', ['title' => 'Sales'])->name('sales.index');
        Route::view('/training', 'erp.coming-soon', ['title' => 'Training'])->name('training.index');
        Route::view('/projects', 'erp.coming-soon', ['title' => 'Projects'])->name('projects.index');
        Route::view('/hr', 'erp.coming-soon', ['title' => 'HR'])->name('hr.index');
        Route::view('/assets', 'erp.coming-soon', ['title' => 'Assets'])->name('assets.index');
    });

    // Settings — each area gated by its own permission.
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::middleware('permission:settings.manage-users')->group(function () {
            Route::resource('users', UserController::class)->except(['show', 'destroy'])->names([
                'index' => 'users',
                'create' => 'users.create',
                'store' => 'users.store',
                'edit' => 'users.edit',
                'update' => 'users.update',
            ]);
        });

        Route::middleware('permission:settings.manage-roles')->group(function () {
            Route::resource('roles', RoleController::class)->except(['show'])->names([
                'index' => 'roles',
                'create' => 'roles.create',
                'store' => 'roles.store',
                'edit' => 'roles.edit',
                'update' => 'roles.update',
                'destroy' => 'roles.destroy',
            ]);
        });

        Route::middleware('permission:settings.manage-system')->group(function () {
            Route::get('/system', [SystemSettingController::class, 'edit'])->name('system');
            Route::put('/system', [SystemSettingController::class, 'update'])->name('system.update');
        });
    });
});
