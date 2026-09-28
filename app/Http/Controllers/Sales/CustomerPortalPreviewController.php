<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Lets staff open the customer portal exactly as a customer sees it, without being able to change anything. */
class CustomerPortalPreviewController extends Controller
{
    public function index(): View
    {
        return view('erp.customer-portal.index', [
            'customers' => Customer::registered()->withCount('sessions')->orderBy('name')->get(),
            'loginUrl' => route('portal.login'),
        ]);
    }

    public function open(Customer $customer): RedirectResponse
    {
        if ($customer->registration_status !== Customer::REGISTRATION_COMPLETE) {
            return redirect()->route('customer-portal.index')->with('error', 'Only approved customers have a portal.');
        }

        Auth::guard('customer')->login($customer);
        session()->regenerate();
        session(['portal_preview' => ['customer_id' => $customer->id, 'user_id' => auth()->id()]]);

        return redirect()->route('portal.dashboard');
    }
}
