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
            'sessions' => $customer->portalSessions()->with(['program', 'meetings.rescheduleRequests', 'instructor', 'payments', 'participants'])->orderBy('start_date')->get(),
            'projects' => $customer->projects()->latest()->get()->groupBy('training_session_id'),
        ]);
    }
}
