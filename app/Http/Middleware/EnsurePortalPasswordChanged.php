<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Sends portal customers still on their initial password to the change-password page first. */
class EnsurePortalPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user('customer')?->must_change_password && ! $request->routeIs('portal.password.*', 'portal.logout')) {
            return redirect()->route('portal.password.edit');
        }

        return $next($request);
    }
}
