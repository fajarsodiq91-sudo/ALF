<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\StoreEmployeeRequest;
use App\Http\Requests\Hr\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Services\ImageCompressor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $employees = Employee::query()
            ->filtered($request)
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('erp.hr.employees.index', ['employees' => $employees]);
    }

    public function create(): View
    {
        $this->authorize('hr.manage');

        return view('erp.hr.employees.create');
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        Employee::create([
            ...$request->safe()->except(['signature', 'photo']),
            ...$this->withUpload($request, new Employee, 'signature', 'signature_path', 'employee-signatures'),
            ...$this->withUpload($request, new Employee, 'photo', 'photo_path', 'employee-photos'),
        ]);

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
        $employee->update([
            ...$request->safe()->except(['signature', 'remove_signature', 'photo', 'remove_photo']),
            ...$this->withUpload($request, $employee, 'signature', 'signature_path', 'employee-signatures'),
            ...$this->withUpload($request, $employee, 'photo', 'photo_path', 'employee-photos'),
        ]);

        return redirect()
            ->route('hr.index')
            ->with('status', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('hr.manage');

        if ($employee->payrolls()->exists()) {
            return redirect()->route('hr.index')
                ->with('error', 'This employee has payroll records and cannot be deleted. Set the status to Resigned instead.');
        }

        $employee->delete();

        return redirect()
            ->route('hr.index')
            ->with('status', 'Employee deleted successfully.');
    }

    /** A new upload replaces the old file; the "remove_<field>" checkbox clears it without replacing it. */
    private function withUpload(Request $request, Employee $employee, string $field, string $column, string $directory): array
    {
        $file = $request->file($field);

        if ($file) {
            $path = ImageCompressor::store($file, $directory, 'public');

            if ($employee->{$column}) {
                Storage::disk('public')->delete($employee->{$column});
            }

            return [$column => $path];
        }

        if ($request->boolean('remove_'.$field) && $employee->{$column}) {
            Storage::disk('public')->delete($employee->{$column});

            return [$column => null];
        }

        return [];
    }
}
