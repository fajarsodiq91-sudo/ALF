<?php

use App\Http\Middleware\EnsurePortalPasswordChanged;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\PortalReadOnlyInPreview;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('finance:charge-bank-admin-fees')->monthlyOn(1, '01:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', EnsureUserIsActive::class);

        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('portal', 'portal/*') ? route('portal.login') : route('login'));

        $middleware->redirectUsersTo(fn (Request $request) => $request->is('portal', 'portal/*') ? route('portal.dashboard') : route('dashboard'));

        $middleware->alias([
            'portal.password' => EnsurePortalPasswordChanged::class,
            'portal.readonly' => PortalReadOnlyInPreview::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
