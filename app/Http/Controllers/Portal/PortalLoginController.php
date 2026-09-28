<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Customers log in with their customer ID as username. */
class PortalLoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function show(): View
    {
        return view('portal.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_code' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
        ]);

        $code = trim($data['customer_code']);
        $throttleKey = Str::lower($code).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($throttleKey) / 60);

            throw ValidationException::withMessages([
                'customer_code' => "Too many login attempts. Please try again in {$minutes} minute(s).",
            ]);
        }

        $loggedIn = Auth::guard('customer')->attempt([
            'customer_code' => $code,
            'password' => $data['password'],
            'registration_status' => Customer::REGISTRATION_COMPLETE,
            'is_active' => true,
        ], $request->boolean('remember'));

        if (! $loggedIn) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages(['customer_code' => 'The customer ID or password is incorrect.']);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->forget('portal_preview'); // a real login is never a preview
        $request->session()->regenerate();

        return redirect()->intended(route('portal.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $wasPreview = $request->session()->pull('portal_preview') !== null;

        Auth::guard('customer')->logout();
        $request->session()->regenerateToken();

        return $wasPreview ? redirect()->route('customer-portal.index')->with('status', 'Left the customer portal preview.') : redirect()->route('portal.login');
    }
}
