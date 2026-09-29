<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\CustomerProject;
use App\Services\ImageCompressor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Where customers hand in the project they built, for the company to consider for its portfolio. */
class PortalProjectController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $customer = $request->user('customer');

        $data = $request->validate([
            'training_session_id' => ['required', Rule::in($customer->portalSessions()->pluck('id')->all())],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'external_url' => ['nullable', 'url', 'max:2048'],
            'file' => ['nullable', 'file', 'mimes:zip,pdf,doc,docx,ppt,pptx,xls,xlsx,csv,txt,png,jpg,jpeg,webp', 'max:10240'],
        ]);

        if (! $request->hasFile('file') && empty($data['external_url'])) {
            return back()->withInput()->withErrors(['file' => 'Attach a file or provide a link to your project.']);
        }

        $file = $request->file('file');

        $customer->projects()->create([
            'training_session_id' => $data['training_session_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'external_url' => $data['external_url'] ?? null,
            'file_path' => $file ? ImageCompressor::store($file, "customer-projects/{$customer->id}", 'local') : null,
            'file_name' => $file?->getClientOriginalName(),
        ]);

        return redirect()->route('portal.dashboard')->with('status', 'Thank you! Your project was uploaded.');
    }

    public function download(Request $request, CustomerProject $project): StreamedResponse
    {
        abort_unless($project->customer_id === $request->user('customer')->id, 403);
        abort_unless($project->file_path && Storage::disk('local')->exists($project->file_path), 404);

        return Storage::disk('local')->download($project->file_path, $project->file_name);
    }
}
