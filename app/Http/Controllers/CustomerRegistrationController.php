<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sales\RegisterCustomerRequest;
use App\Mail\CustomerRegistrationReceived;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

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
            $customer->submitRegistration($request->safe()->except('photo'));
        } catch (Throwable $exception) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }

            throw $exception;
        }

        try {
            Mail::to($customer->email)->send(new CustomerRegistrationReceived($customer));
        } catch (Throwable $exception) {
            // The registration itself is saved; a mail problem must not make the customer resubmit.
            Log::error('Could not send the registration confirmation email.', ['customer_id' => $customer->id, 'error' => $exception->getMessage()]);
        }

        return redirect()
            ->route('customer-registration.done')
            ->with('registered', ['name' => $customer->name, 'email' => $customer->email]);
    }

    public function done(): View
    {
        return view('customer-registration.done', ['registered' => session('registered')]);
    }
}
