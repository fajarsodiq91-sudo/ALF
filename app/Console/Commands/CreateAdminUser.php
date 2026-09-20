<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Console\Command;

class CreateAdminUser extends Command
{
    protected $signature = 'erp:create-admin {email} {--name=Administrator}';

    protected $description = 'Create (or promote) a Super Admin user, prompting for the password';

    public function handle(): int
    {
        $this->callSilent('db:seed', ['--class' => RolePermissionSeeder::class, '--force' => true]);

        $email = $this->argument('email');
        $password = $this->secret('Password (min 8 characters)');

        if (strlen((string) $password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => $this->option('name'), 'password' => $password, 'is_active' => true],
        );
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->syncRoles(['Super Admin']);

        $this->info("Super Admin ready: {$email}");

        return self::SUCCESS;
    }
}
