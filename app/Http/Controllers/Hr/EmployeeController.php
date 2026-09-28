<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\StoreEmployeeRequest;
use App\Http\Requests\Hr\UpdateEmployeeRequest;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $employees = Employee::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($query) => $query->where('employment_type', $request->string('type')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(fn ($inner) => $inner->where('name', 'like', $term)
                    ->orWhere('employee_number', 'like', $term)
                    ->orWhere('position', 'like', $term)
                    ->orWhere('department', 'like', $term));
            })
            ->orderBy('name')
            ->get();

        return view('erp.hr.employees.index', ['employees' => $employees]);
    }

    public function create(): View
    {
        $this->authorize('hr.manage');

        return view('erp.hr.employees.create');
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        Employee::create($request->validated());

        return redirect()
            ->route('hr.index')
            ->with('status', 'Employee created successfully.');
    }

    public function edit(Employee $employee): View
    {
        $this->authorize('hr.manage');

        return view('erp.hr.employees.edit', ['employee' => $employee]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return redirect()
            ->route('hr.index')
            ->with('status', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('hr.manage');

        $employee->delete();

        return redirect()
            ->route('hr.index')
            ->with('status', 'Employee deleted successfully.');
    }
}
