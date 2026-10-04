<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** Uploaded certificate backgrounds and where the dynamic fields sit on them. */
class CertificateTemplateController extends Controller
{
    public function index(): View
    {
        return view('erp.settings.certificate-templates.index', ['templates' => CertificateTemplate::withCount('programs')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('erp.settings.certificate-templates.form', ['template' => new CertificateTemplate]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, true);
        $path = $request->file('background')->store('certificate-templates', 'public');

        $template = CertificateTemplate::create([
            'name' => $data['name'],
            'background_path' => $path,
            'layout' => $data['layout'] ?? null,
            'is_default' => false,
        ]);
        $this->applyDefault($template, $request);

        return redirect()->route('settings.certificate-templates.index')->with('status', 'Template saved.');
    }

    public function edit(CertificateTemplate $certificateTemplate): View
    {
        return view('erp.settings.certificate-templates.form', ['template' => $certificateTemplate]);
    }

    public function update(Request $request, CertificateTemplate $certificateTemplate): RedirectResponse
    {
        $data = $this->validated($request, false);
        $attributes = ['name' => $data['name'], 'layout' => $data['layout'] ?? null];

        if ($request->hasFile('background')) {
            Storage::disk('public')->delete($certificateTemplate->background_path);
            $attributes['background_path'] = $request->file('background')->store('certificate-templates', 'public');
        }

        $certificateTemplate->update($attributes);
        $this->applyDefault($certificateTemplate, $request);

        return redirect()->route('settings.certificate-templates.index')->with('status', 'Template updated.');
    }

    public function destroy(CertificateTemplate $certificateTemplate): RedirectResponse
    {
        Storage::disk('public')->delete($certificateTemplate->background_path);
        $certificateTemplate->delete();

        return redirect()->route('settings.certificate-templates.index')->with('status', 'Template deleted.');
    }

    /** Renders a sample certificate (no real holder) so positions can be checked right after uploading. */
    public function preview(CertificateTemplate $certificateTemplate): View
    {
        return view('certificates.sample', ['template' => $certificateTemplate]);
    }

    private function validated(Request $request, bool $creating): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'background' => [$creating ? 'required' : 'nullable', 'image', 'mimes:png,jpg,jpeg', 'max:15360', 'dimensions:min_width=1600,ratio=297/210',
                function ($attribute, $value, $fail) {
                    if ($value->getMimeType() === 'image/png' && ! extension_loaded('gd')) {
                        $fail('This server cannot put PNG templates into PDFs (PHP GD extension missing). Upload the template as JPG, or enable GD.');
                    }
                },
            ],
        ];

        foreach (array_keys(CertificateTemplate::FIELDS) as $field) {
            $rules["layout.$field.x"] = ['nullable', 'numeric', 'between:0,297'];
            $rules["layout.$field.y"] = ['nullable', 'numeric', 'between:0,210'];
            $rules["layout.$field.w"] = ['nullable', 'numeric', 'between:5,297'];
        }

        return $request->validate($rules, ['background.dimensions' => 'The template must be A4 landscape (297 x 210 mm) and at least 1600 px wide.']);
    }

    /** Only one default; certificates without a program-specific template use it. */
    private function applyDefault(CertificateTemplate $template, Request $request): void
    {
        if ($request->boolean('is_default')) {
            CertificateTemplate::where('id', '!=', $template->id)->update(['is_default' => false]);
            $template->update(['is_default' => true]);
        } elseif ($template->is_default) {
            $template->update(['is_default' => false]);
        }
    }
}
