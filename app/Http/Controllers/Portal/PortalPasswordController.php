<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PortalPasswordController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('portal_preview')) {
            return redirect()->route('portal.dashboard')->with('error', 'Passwords cannot be changed in preview mode.');
        }

        return view('portal.password', ['forced' => $request->user('customer')->must_change_password]);
    }

    public function update(Request $request): RedirectResponse
    {
        $customer = $request->user('customer');

        $data = $request->validate([
            'current_password' => ['required', 'current_password:customer'],
            'password' => ['required', 'confirmed', Password::min(8), 'not_in:'.$customer->customer_code],
        ], [
            'password.not_in' => 'Your new password cannot be the same as your customer ID.',
        ]);

        // Credentials are deliberately not mass-assignable on Customer, so they are set explicitly.
        $customer->forceFill(['password' => $data['password'], 'must_change_password' => false])->save();

        return redirect()->route('portal.dashboard')->with('status', 'Your password has been changed.');
    }
}
