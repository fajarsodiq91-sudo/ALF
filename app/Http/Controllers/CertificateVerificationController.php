<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Response;
use Illuminate\View\View;

/** Public, read-only proof that a certificate is genuine — reached by scanning the QR code printed on it. */
class CertificateVerificationController extends Controller
{
    public function show(string $code): View|Response
    {
        $certificate = Certificate::where('verification_code', $code)->with(['customer', 'session.program'])->first();

        if (! $certificate) {
            return response()->view('certificates.verify-invalid', [], 404);
        }

        return view('certificates.verify', ['certificate' => $certificate]);
    }
}
