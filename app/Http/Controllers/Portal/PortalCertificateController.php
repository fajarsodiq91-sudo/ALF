<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** A customer's own certificates, printable from the browser. */
class PortalCertificateController extends Controller
{
    public function index(Request $request): View
    {
        $certificates = Certificate::where('customer_id', $request->user('customer')->id)->with('session.program')->latest('issued_at')->latest('id')->get();

        return view('portal.certificates.index', ['certificates' => $certificates]);
    }

    public function show(Request $request, Certificate $certificate): View
    {
        abort_unless($certificate->customer_id === $request->user('customer')->id, 403);

        return view('portal.certificates.show', ['certificate' => $certificate->load(['session.program', 'session.meetings', 'session.instructor', 'customer'])]);
    }
}
