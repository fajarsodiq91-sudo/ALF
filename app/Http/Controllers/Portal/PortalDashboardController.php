<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $customer = $request->user('customer');

        return view('portal.dashboard', [
            'customer' => $customer,
            'sessions' => $customer->sessions()->with(['program', 'meetings', 'instructor', 'payments'])->orderBy('start_date')->get(),
            'projects' => $customer->projects()->latest()->get()->groupBy('training_session_id'),
        ]);
    }
}
