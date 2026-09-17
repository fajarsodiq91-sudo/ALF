<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/erp', '/erp/dashboard');

Route::get('/erp/dashboard', function () {
    return view('erp.dashboard');
})->middleware(['auth', 'verified', 'permission:access-erp'])->name('dashboard');

Route::middleware(['auth', 'verified'])->prefix('erp')->group(function () {

    // Finance — nav structure only for now; each page becomes real in its own phase.
    Route::redirect('/finance', '/finance/dashboard');

    Route::middleware('permission:finance.view')->prefix('finance')->name('finance.')->group(function () {
        Route::view('/dashboard', 'erp.coming-soon', [
            'title' => 'Finance Dashboard',
            'description' => 'Balance, income, expense, and cash-flow widgets are built in the Finance Dashboard phase, once real transaction data exists.',
        ])->name('dashboard');

        Route::view('/accounts', 'erp.coming-soon', [
            'title' => 'Accounts',
            'description' => 'Manage company funding sources (cash, bank, e-wallet). Built in the Accounts & Categories phase.',
        ])->name('accounts');

        Route::view('/categories', 'erp.coming-soon', [
            'title' => 'Categories',
            'description' => 'Configurable income and expense categories. Built in the Accounts & Categories phase.',
        ])->name('categories');

        Route::view('/income', 'erp.coming-soon', [
            'title' => 'Income',
            'description' => 'Record company income with auto-numbered transactions. Built in the Income phase.',
        ])->name('income');

        Route::view('/expenses', 'erp.coming-soon', [
            'title' => 'Expenses',
            'description' => 'Record company expenses with auto-numbered transactions. Built in the Expense phase.',
        ])->name('expenses');

        Route::view('/transfers', 'erp.coming-soon', [
            'title' => 'Transfers',
            'description' => 'Move funds between company accounts without affecting income or expense totals. Built in the Transfer phase.',
        ])->name('transfers');

        Route::view('/transactions', 'erp.coming-soon', [
            'title' => 'Transactions',
            'description' => 'A combined, filterable ledger of income, expenses, and transfers. Built in the Transaction Ledger phase.',
        ])->name('transactions');

        Route::view('/reports', 'erp.coming-soon', [
            'title' => 'Reports',
            'description' => 'Income, expense, cash-flow, account balance, and monthly summary reports. Built in the Financial Reports phase.',
        ])->name('reports');
    });

    // Planned modules — placeholder pages only, no functionality yet.
    Route::middleware('permission:access-erp')->group(function () {
        Route::view('/sales', 'erp.coming-soon', ['title' => 'Sales'])->name('sales.index');
        Route::view('/training', 'erp.coming-soon', ['title' => 'Training'])->name('training.index');
        Route::view('/projects', 'erp.coming-soon', ['title' => 'Projects'])->name('projects.index');
        Route::view('/hr', 'erp.coming-soon', ['title' => 'HR'])->name('hr.index');
        Route::view('/assets', 'erp.coming-soon', ['title' => 'Assets'])->name('assets.index');
    });

    // Settings — each item gated by its own permission.
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::view('/users', 'erp.coming-soon', [
            'title' => 'Users',
            'description' => 'Manage user accounts and role assignments.',
        ])->middleware('permission:settings.manage-users')->name('users');

        Route::view('/roles', 'erp.coming-soon', [
            'title' => 'Roles',
            'description' => 'Manage roles and their permissions.',
        ])->middleware('permission:settings.manage-roles')->name('roles');

        Route::view('/system', 'erp.coming-soon', [
            'title' => 'System Settings',
            'description' => 'General application configuration.',
        ])->middleware('permission:settings.manage-system')->name('system');
    });
});
