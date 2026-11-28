<?php

use App\Models\Employee;
use App\Services\EmployeeLogin;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** Employees who were already on staff (not Resigned) before employee logins existed get theirs now. */
    public function up(): void
    {
        Employee::query()->where('status', '!=', 'resigned')->orderBy('id')->each(fn (Employee $employee) => EmployeeLogin::sync($employee));
    }

    public function down(): void
    {
        // Nothing to undo: the accounts are ordinary users, and rolling back the previous migration unlinks them.
    }
};
