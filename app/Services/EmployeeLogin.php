<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Keeps each employee's ERP login in step with their HR status: anyone not Resigned can log in
 * (with their employee number, first on a password equal to it), anyone else cannot.
 */
class EmployeeLogin
{
    /** The role new employee accounts start with: ERP access only; more is granted in Settings → Users. */
    public const ROLE = 'Staff';

    public static function sync(Employee $employee): void
    {
        $user = $employee->user()->first(); // never a cached relation: one save can trigger another

        if (! self::canLogIn($employee)) {
            self::deactivate($user);

            return;
        }

        if (! $user) {
            self::linkOrCreate($employee);

            return;
        }

        // Only a change of status (back from Resigned) re-opens the account, so a login an admin switched off stays off on unrelated edits.
        if (! $user->is_active && $employee->wasChanged('status')) {
            $user->update(['is_active' => true]);
        }
    }

    /** Everyone still employed (Active or On Leave) can log in; only Resigned cannot. */
    public static function canLogIn(Employee $employee): bool
    {
        return $employee->status !== 'resigned';
    }

    public static function deactivate(?User $user): void
    {
        if (! $user || ! $user->is_active || self::isLastActiveSuperAdmin($user)) {
            return;
        }

        $user->update(['is_active' => false]);
    }

    /** An existing account with the employee's email becomes theirs; otherwise a fresh Staff account is made. */
    private static function linkOrCreate(Employee $employee): void
    {
        $email = $employee->email ? Str::lower(trim($employee->email)) : null;
        $existing = $email ? User::query()->where('email', $email)->first() : null;

        if ($existing && $existing->employee_id === null) {
            $existing->forceFill(['employee_id' => $employee->id, 'is_active' => true])->save();
            $employee->setRelation('user', $existing);

            return;
        }

        $user = new User([
            'name' => $employee->name,
            'email' => $existing ? null : $email, // the address already belongs to another employee's account
            'password' => $employee->employee_number,
            'is_active' => true,
        ]);
        $user->forceFill(['employee_id' => $employee->id, 'must_change_password' => true, 'email_verified_at' => now()])->save();
        $user->assignRole(Role::findOrCreate(self::ROLE, 'web'));
        $employee->setRelation('user', $user);
    }

    private static function isLastActiveSuperAdmin(User $user): bool
    {
        return $user->hasRole('Super Admin')
            && User::role('Super Admin')->where('is_active', true)->count() <= 1;
    }
}
