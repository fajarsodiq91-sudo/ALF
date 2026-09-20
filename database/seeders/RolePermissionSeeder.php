<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Permissions available today. New modules add their own permissions
     * here as they're built (e.g. "sales.view", "hr.manage") instead of
     * hard-coding module access anywhere else in the app.
     */
    private const PERMISSIONS = [
        'access-erp',
        'finance.view',
        'finance.manage',
        'finance.reports',
        'settings.manage-users',
        'settings.manage-roles',
        'settings.manage-system',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(self::PERMISSIONS);

        // Default permissions only apply when a role is first created, so
        // customisations made in Settings > Roles survive re-seeding on deploy.
        $defaults = [
            'Finance' => ['access-erp', 'finance.view', 'finance.manage', 'finance.reports'],
            'Staff' => ['access-erp'],
            'Viewer' => ['access-erp', 'finance.view'],
        ];

        foreach ($defaults as $name => $permissions) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

            if ($role->wasRecentlyCreated) {
                $role->syncPermissions($permissions);
            }
        }
    }
}
