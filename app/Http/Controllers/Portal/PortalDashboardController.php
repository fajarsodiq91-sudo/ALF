<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
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
            // Keyed by session id: the real, auto-issued certificate (with PDF/QR) takes priority over the older manual certificate_url link.
            'certificates' => Certificate::where('customer_id', $customer->id)->get()->keyBy('training_session_id'),
        ]);
    }
}
