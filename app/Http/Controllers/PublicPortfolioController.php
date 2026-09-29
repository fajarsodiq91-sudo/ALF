<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Customer;
use App\Models\CustomerProject;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** A customer's public, login-free showcase — reached by scanning the QR code on their certificate. */
class PublicPortfolioController extends Controller
{
    public function show(string $token): View|Response
    {
        $customer = Customer::findByPortfolioToken($token);

        if (! $customer) {
            return response()->view('portfolio-public.invalid', [], 404);
        }

        return view('portfolio-public.show', [
            'customer' => $customer,
            'projects' => $customer->projects()->where('in_portfolio', true)->latest()->get(),
            'certificates' => Certificate::where('customer_id', $customer->id)->with('session.program')->latest('issued_at')->get(),
        ]);
    }

    /** Only files the customer (or staff, on their behalf) chose to showcase are reachable here. */
    public function download(CustomerProject $project): StreamedResponse
    {
        abort_unless($project->in_portfolio && $project->file_path && Storage::disk('local')->exists($project->file_path), 404);

        return Storage::disk('local')->download($project->file_path, $project->file_name);
    }
}
