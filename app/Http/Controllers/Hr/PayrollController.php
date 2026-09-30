<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\PayPayrollRequest;
use App\Http\Requests\Hr\SavePayrollRequest;
use App\Models\Account;
use App\Models\Category;
use App\Models\Employee;
use App\Models\ExpenseTransaction;
use App\Models\Payroll;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function index(Request $request): View
    {
        $periodGiven = preg_match('/^\d{4}-\d{2}$/', (string) $request->input('period')) === 1;
        $period = $periodGiven
            ? $request->input('period')
            : ($request->filled('status') ? null : now()->format('Y-m'));

        // Column names are qualified because paginating joins the employees table, which also has a "status" column.
        $filters = fn ($query) => $query
            ->when($period, fn ($query) => $query->where('payrolls.period', $period))
            ->when($request->filled('status'), fn ($query) => $query->where('payrolls.status', $request->string('status')));

        $totalNet = (float) Payroll::query()->tap($filters)->sum('net_salary');

        $payrolls = Payroll::query()
            ->with('employee')
            ->tap($filters)
            ->join('employees', 'employees.id', '=', 'payrolls.employee_id')
            ->orderBy('employees.name')
            ->select('payrolls.*')
            ->paginate(20)
            ->withQueryString();

        return view('erp.hr.payroll.index', [
            'payrolls' => $payrolls,
            'period' => $period,
            'totalNet' => $totalNet,
            'accounts' => Account::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('erp.hr.payroll.create', [
            'employees' => Employee::where('status', '!=', 'resigned')->orderBy('name')->get(),
            'period' => $request->input('period', now()->format('Y-m')),
        ]);
    }

    public function store(SavePayrollRequest $request): RedirectResponse
    {
        $payroll = Payroll::create($request->payrollData());

        return redirect()->route('hr.payroll.index', ['period' => $payroll->period])->with('status', 'Payroll created.');
    }

    public function show(Payroll $payroll): View
    {
        return view('erp.hr.payroll.show', ['payroll' => $payroll->load('employee')]);
    }

    public function edit(Payroll $payroll): View|RedirectResponse
    {
        if ($payroll->isPaid()) {
            return $this->lockedResponse($payroll);
        }

        return view('erp.hr.payroll.edit', [
            'payroll' => $payroll,
            'employees' => Employee::orderBy('name')->get(),
        ]);
    }

    public function update(SavePayrollRequest $request, Payroll $payroll): RedirectResponse
    {
        if ($payroll->isPaid()) {
            return $this->lockedResponse($payroll);
        }

        $payroll->update($request->payrollData());

        return redirect()->route('hr.payroll.index', ['period' => $payroll->period])->with('status', 'Payroll updated.');
    }

    public function destroy(Payroll $payroll): RedirectResponse
    {
        if ($payroll->isPaid()) {
            return $this->lockedResponse($payroll);
        }

        $payroll->delete();

        return redirect()->route('hr.payroll.index', ['period' => $payroll->period])->with('status', 'Payroll deleted.');
    }

    /** Marks the payroll paid and records the matching expense in Finance. */
    public function pay(PayPayrollRequest $request, Payroll $payroll): RedirectResponse
    {
        if ($payroll->isPaid()) {
            return $this->lockedResponse($payroll);
        }

        DB::transaction(function () use ($request, $payroll) {
            $category = Category::firstOrCreate(
                ['name' => Payroll::SALARY_CATEGORY, 'type' => 'expense'],
                ['is_active' => true, 'description' => 'Employee salaries paid through HR payroll'],
            );

            $expense = ExpenseTransaction::create([
                'transaction_number' => 'EXP-'.date('YmdHis').'-'.rand(1000, 9999),
                'transaction_date' => $request->input('paid_date'),
                'account_id' => $request->input('account_id'),
                'category_id' => $category->id,
                'payee' => $payroll->employee->name,
                'description' => "Salary {$payroll->period} ({$payroll->employee->employee_number})",
                'subtotal' => $payroll->net_salary,
                'tax_amount' => 0,
                'amount' => $payroll->net_salary,
                'payment_method' => $request->input('payment_method'),
                'created_by' => auth()->id(),
            ]);

            $payroll->update([
                'status' => Payroll::PAID,
                'paid_date' => $request->input('paid_date'),
                'expense_transaction_id' => $expense->id,
            ]);
        });

        return redirect()->route('hr.payroll.index', ['period' => $payroll->period])
            ->with('status', 'Payroll paid and recorded as an expense in Finance.');
    }

    /** Reverts a paid payroll to draft and removes its Finance expense. */
    public function cancelPayment(Payroll $payroll): RedirectResponse
    {
        $this->authorize('finance.manage');

        if (! $payroll->isPaid()) {
            return redirect()->route('hr.payroll.index', ['period' => $payroll->period])->with('error', 'This payroll is not paid.');
        }

        DB::transaction(function () use ($payroll) {
            $payroll->expenseTransaction?->delete();
            $payroll->update(['status' => Payroll::DRAFT, 'paid_date' => null, 'expense_transaction_id' => null]);
        });

        return redirect()->route('hr.payroll.index', ['period' => $payroll->period])
            ->with('status', 'Payment cancelled and the Finance expense removed.');
    }

    private function lockedResponse(Payroll $payroll): RedirectResponse
    {
        return redirect()->route('hr.payroll.index', ['period' => $payroll->period])
            ->with('error', 'Paid payrolls are locked. Cancel the payment first to change or delete it.');
    }
}
