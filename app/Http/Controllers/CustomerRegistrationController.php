<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sales\RegisterCustomerRequest;
use App\Mail\CustomerRegistrationReceived;
use App\Models\Customer;
use App\Models\TrainingProgram;
use App\Services\BookedSlots;
use App\Services\ImageCompressor;
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

        $programs = TrainingProgram::with('category')->where('is_active', true)->orderBy('name')->get();

        $grouped = $programs->groupBy(fn ($program) => $program->category?->name ?? 'Lainnya')
            ->sortKeysUsing(fn ($a, $b) => match (true) {
                $a === 'Lainnya' => 1,
                $b === 'Lainnya' => -1,
                default => strcmp($a, $b),
            });

        return view('customer-registration.form', [
            'customer' => $customer,
            'token' => $token,
            'programs' => $grouped,
            'prices' => $programs->mapWithKeys(fn ($program) => [$program->id => (float) $program->standard_price]),
            'booked' => BookedSlots::keys(),
            'sessionMinutes' => $programs->mapWithKeys(fn ($program) => [$program->id => $program->session_minutes]),
            'meetingCounts' => $programs->mapWithKeys(fn ($program) => [$program->id => $program->duration_days]),
        ]);
    }

    public function store(RegisterCustomerRequest $request, string $token): RedirectResponse|Response
    {
        $customer = Customer::findByValidRegistrationToken($token);

        if (! $customer) {
            return response()->view('customer-registration.invalid', [], 410);
        }

        $photo = $request->file('photo');
        $photoPath = $photo ? ImageCompressor::store($photo, 'customer-photos', 'public') : null;

        try {
            $customer->photo_path = $photoPath;
            $customer->submitRegistration([
                ...$request->safe()->except(['photo', 'programs']),
                'requested_programs' => $request->requestedPrograms() ?: null,
            ]);
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

        session(['registration_status_token' => $customer->status_token]);

        return redirect()->route('customer-registration.status', $customer->status_token);
    }

    /** The live status of a registration: waiting for approval, approved (with the customer ID), or not approved. */
    public function status(string $token): View|Response
    {
        $customer = Customer::findByStatusToken($token);

        if (! $customer) {
            return response()->view('customer-registration.invalid', [], 404);
        }

        return view('customer-registration.status', [
            'customer' => $customer,
            'requested' => $customer->isPendingApproval() ? $customer->requestedProgramSummaries() : [],
            'sessions' => $customer->registration_status === Customer::REGISTRATION_COMPLETE
                ? $customer->sessions()->with(['program', 'meetings', 'payments'])->orderBy('start_date')->get()
                : collect(),
        ]);
    }

    /** Old address of the thank-you page: send people who registered in this browser to their live status. */
    public function done(): View|RedirectResponse
    {
        $token = session('registration_status_token');

        if ($token && Customer::findByStatusToken($token)) {
            return redirect()->route('customer-registration.status', $token);
        }

        return view('customer-registration.done');
    }
}
