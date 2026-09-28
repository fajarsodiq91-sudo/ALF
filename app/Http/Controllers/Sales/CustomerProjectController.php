<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\CustomerProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** ERP side of the projects customers upload through the portal. */
class CustomerProjectController extends Controller
{
    public function download(CustomerProject $project): StreamedResponse
    {
        abort_unless($project->file_path && Storage::disk('local')->exists($project->file_path), 404);

        return Storage::disk('local')->download($project->file_path, $project->file_name);
    }

    public function togglePortfolio(CustomerProject $project): RedirectResponse
    {
        $this->authorize('sales.manage');

        $project->update(['in_portfolio' => ! $project->in_portfolio]);

        return redirect()->route('sales.show', $project->customer_id)
            ->with('status', $project->in_portfolio ? 'Marked as added to the portfolio.' : 'Removed the portfolio mark.');
    }
}
