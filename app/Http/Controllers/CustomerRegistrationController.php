<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sales\RegisterCustomerRequest;
use App\Models\Customer;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** Public, login-free pages a customer reaches by scanning the QR code from the ERP. */
class CustomerRegistrationController extends Controller
{
    public function show(string $token): View|Response
    {
        $customer = Customer::findByValidRegistrationToken($token);

        if (! $customer) {
            return response()->view('customer-registration.invalid', [], 410);
        }

        return view('customer-registration.form', ['customer' => $customer, 'token' => $token]);
    }

    public function store(RegisterCustomerRequest $request, string $token): RedirectResponse|Response
    {
        $customer = Customer::findByValidRegistrationToken($token);

        if (! $customer) {
            return response()->view('customer-registration.invalid', [], 410);
        }

        $photoPath = $request->file('photo')?->store('customer-photos', 'public');

        try {
            $customer->photo_path = $photoPath;
            $customer->completeRegistration($request->safe()->except('photo'));
        } catch (DomainException) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }

            return back()->withInput()->withErrors(['name' => 'Registration is temporarily unavailable. Please contact PT Alfajar Logic Futura.']);
        }

        return redirect()
            ->route('customer-registration.done')
            ->with('registered', ['code' => $customer->customer_code, 'name' => $customer->name]);
    }

    public function done(): View
    {
        return view('customer-registration.done', ['registered' => session('registered')]);
    }
}
