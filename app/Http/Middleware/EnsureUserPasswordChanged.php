<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Sends staff still on their initial password (employee accounts start on their employee number) to the profile page to change it first. */
class EnsureUserPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('erp', 'erp/*') && $request->user('web')?->must_change_password) {
            return redirect()->route('profile.edit');
        }

        return $next($request);
    }
}
