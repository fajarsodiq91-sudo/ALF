<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** While staff preview the portal as a customer, nothing may be changed: only reading (and leaving the preview) is allowed. */
class PortalReadOnlyInPreview
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('portal_preview') && ! $request->isMethodSafe() && ! $request->routeIs('portal.logout')) {
            return redirect()->route('portal.dashboard')->with('error', 'Preview mode is read-only. Nothing was changed.');
        }

        return $next($request);
    }
}
