<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** A customer's own certificates, viewable and downloadable as a PDF. */
class PortalCertificateController extends Controller
{
    public function index(Request $request): View
    {
        $certificates = Certificate::where('customer_id', $request->user('customer')->id)->with('session.program')->latest('issued_at')->latest('id')->paginate(20);

        return view('portal.certificates.index', ['certificates' => $certificates]);
    }

    public function show(Request $request, Certificate $certificate): View
    {
        abort_unless($certificate->customer_id === $request->user('customer')->id, 403);

        return view('portal.certificates.show', ['certificate' => $this->loadCertificate($certificate)]);
    }

    public function download(Request $request, Certificate $certificate): Response
    {
        abort_unless($certificate->customer_id === $request->user('customer')->id, 403);

        $certificate = $this->loadCertificate($certificate);

        return Pdf::loadView('certificates.pdf', ['certificate' => $certificate])
            ->setPaper('a4', 'landscape')
            ->download(str_replace('/', '-', $certificate->number).'.pdf');
    }

    private function loadCertificate(Certificate $certificate): Certificate
    {
        return $certificate->load(['session.program', 'session.meetings', 'session.instructor', 'customer']);
    }
}
