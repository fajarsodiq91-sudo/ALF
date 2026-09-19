<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
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

        // Codespaces port-forwarding proxies rewrite the Host header to
        // localhost before it reaches `php artisan serve`, which makes
        // Laravel generate localhost redirect/asset URLs. Force every
        // generated URL to use the public APP_URL instead.
        if (str(config('app.url'))->startsWith('https://')) {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }
    }
}
