<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Super Admin always passes authorization checks, so new
        // permissions never need to be manually granted to this role.
        Gate::before(fn ($user, string $ability) => $user->hasRole('Super Admin') ? true : null);
    }
}
