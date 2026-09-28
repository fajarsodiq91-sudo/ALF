<?php

use App\Http\Controllers\Assets\AssetController;
use App\Http\Controllers\Finance\AccountController;
use App\Http\Controllers\Finance\CategoryController;
use App\Http\Controllers\Finance\DashboardController;
use App\Http\Controllers\Finance\ExpenseTransactionController;
use App\Http\Controllers\Finance\IncomeTransactionController;
use App\Http\Controllers\Finance\LoanController;
use App\Http\Controllers\Finance\LoanRepaymentController;
use App\Http\Controllers\Finance\ReportController;
use App\Http\Controllers\Finance\TaxController;
use App\Http\Controllers\Finance\TaxPaymentController;
use App\Http\Controllers\Finance\TransactionLedgerController;
use App\Http\Controllers\Finance\TransferTransactionController;
use App\Http\Controllers\Hr\AttendanceController;
use App\Http\Controllers\Hr\EmployeeController;
use App\Http\Controllers\Hr\LeaveRequestController;
use App\Http\Controllers\Hr\PayrollController;
use App\Http\Controllers\Projects\ProjectController;
use App\Http\Controllers\Projects\ProjectTaskController;
use App\Http\Controllers\Sales\CustomerController;
use App\Http\Controllers\Sales\CustomerPortalPreviewController;
use App\Http\Controllers\Sales\CustomerProjectController;
use App\Http\Controllers\Settings\MasterDataController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\SystemSettingController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Training\TrainingProgramController;
use App\Http\Controllers\Training\TrainingSessionController;
use App\Http\Controllers\Training\TrainingSessionMeetingController;
use App\Http\Controllers\Training\TrainingSessionPaymentController;
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

    Route::middleware('permission:sales.view')->group(function () {
        Route::get('customer-portal', [CustomerPortalPreviewController::class, 'index'])->name('customer-portal.index');
        Route::get('customer-portal/{customer}/open', [CustomerPortalPreviewController::class, 'open'])->name('customer-portal.open');
    });

    Route::middleware('permission:projects.view')->group(function () {
        Route::resource('projects', ProjectController::class)->names('projects');
        Route::post('projects/{project}/tasks', [ProjectTaskController::class, 'store'])->name('projects.tasks.store');
        Route::patch('project-tasks/{task}', [ProjectTaskController::class, 'update'])->name('projects.tasks.update');
        Route::delete('project-tasks/{task}', [ProjectTaskController::class, 'destroy'])->name('projects.tasks.destroy');
    });

    Route::middleware('permission:assets.view')->group(function () {
        Route::resource('assets', AssetController::class)->except(['show'])->names([
            'index' => 'assets.index',
            'create' => 'assets.create',
            'store' => 'assets.store',
            'edit' => 'assets.edit',
            'update' => 'assets.update',
            'destroy' => 'assets.destroy',
        ]);
    });

    Route::middleware('permission:sales.view')->group(function () {
        Route::post('sales/invite', [CustomerController::class, 'invite'])->name('sales.invite.store');
        Route::get('sales/{customer}/invite', [CustomerController::class, 'showInvite'])->name('sales.invite.show');
        Route::post('sales/{customer}/invite/regenerate', [CustomerController::class, 'regenerateInvite'])->name('sales.invite.regenerate');
        Route::get('sales/projects/{project}/download', [CustomerProjectController::class, 'download'])->name('sales.projects.download');
        Route::patch('sales/projects/{project}/portfolio', [CustomerProjectController::class, 'togglePortfolio'])->name('sales.projects.portfolio');
        Route::get('sales/{customer}/review', [CustomerController::class, 'review'])->name('sales.review');
        Route::post('sales/{customer}/approve', [CustomerController::class, 'approve'])->name('sales.approve');
        Route::post('sales/{customer}/reject', [CustomerController::class, 'reject'])->name('sales.reject');
        Route::resource('sales', CustomerController::class)->parameters(['sales' => 'customer'])->names([
            'index' => 'sales.index',
            'create' => 'sales.create',
            'store' => 'sales.store',
            'show' => 'sales.show',
            'edit' => 'sales.edit',
            'update' => 'sales.update',
            'destroy' => 'sales.destroy',
        ]);
    });

    Route::middleware('permission:training.view')->prefix('training')->group(function () {
        Route::resource('programs', TrainingProgramController::class)->except(['show'])
            ->parameters(['programs' => 'program'])->names('training.programs');
    });

    Route::middleware('permission:training.view')->prefix('training')->group(function () {
        Route::post('{session}/meetings', [TrainingSessionMeetingController::class, 'store'])->name('training.meetings.store');
        Route::patch('meetings/{meeting}/toggle', [TrainingSessionMeetingController::class, 'toggle'])->name('training.meetings.toggle');
        Route::delete('meetings/{meeting}', [TrainingSessionMeetingController::class, 'destroy'])->name('training.meetings.destroy');
        Route::post('payments/{payment}/pay', [TrainingSessionPaymentController::class, 'pay'])->name('training.payments.pay');
        Route::post('payments/{payment}/cancel', [TrainingSessionPaymentController::class, 'cancel'])->name('training.payments.cancel');
    });

    Route::middleware('permission:training.view')->group(function () {
        Route::resource('training', TrainingSessionController::class)->except(['show'])
            ->parameters(['training' => 'session'])->names('training');
    });

    Route::middleware('permission:hr.view')->prefix('hr')->group(function () {
        Route::resource('leaves', LeaveRequestController::class)->only(['index', 'create', 'store', 'destroy'])
            ->parameters(['leaves' => 'leave'])->names('hr.leaves');
        Route::post('leaves/{leave}/approve', [LeaveRequestController::class, 'approve'])->name('hr.leaves.approve');
        Route::post('leaves/{leave}/reject', [LeaveRequestController::class, 'reject'])->name('hr.leaves.reject');

        Route::resource('attendance', AttendanceController::class)->except(['show'])->names('hr.attendance');
    });

    Route::middleware('permission:hr.payroll')->prefix('hr')->group(function () {
        Route::resource('payroll', PayrollController::class)->names('hr.payroll');
        Route::post('payroll/{payroll}/pay', [PayrollController::class, 'pay'])->name('hr.payroll.pay');
        Route::post('payroll/{payroll}/cancel-payment', [PayrollController::class, 'cancelPayment'])->name('hr.payroll.cancel-payment');
    });

    Route::middleware('permission:hr.view')->group(function () {
        Route::resource('hr', EmployeeController::class)->except(['show'])->parameters(['hr' => 'employee'])->names([
            'index' => 'hr.index',
            'create' => 'hr.create',
            'store' => 'hr.store',
            'edit' => 'hr.edit',
            'update' => 'hr.update',
            'destroy' => 'hr.destroy',
        ]);
    });

    Route::middleware('permission:masterdata.manage')->group(function () {
        Route::resource('master-data', MasterDataController::class)->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['master-data' => 'masterDataItem'])->names('masterdata');
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
            Route::put('/system/operating-hours', [SystemSettingController::class, 'updateOperatingHours'])->name('system.hours.update');
        });
    });
});
