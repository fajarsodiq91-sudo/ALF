<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Local/dev test accounts, one per role, for exercising authentication
 * and authorization during development. Passwords are only ever the
 * fixed dev value below in non-production environments.
 */
class UserSeeder extends Seeder
{
    private const TEST_ACCOUNTS = [
        ['name' => 'Super Admin', 'email' => 'superadmin@alfajarlogic.test', 'role' => 'Super Admin'],
        ['name' => 'Finance User', 'email' => 'finance@alfajarlogic.test', 'role' => 'Finance'],
        ['name' => 'Staff User', 'email' => 'staff@alfajarlogic.test', 'role' => 'Staff'],
        ['name' => 'Viewer User', 'email' => 'viewer@alfajarlogic.test', 'role' => 'Viewer'],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        foreach (self::TEST_ACCOUNTS as $account) {
            $user = User::firstOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$account['role']]);
        }
    }
}
