<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Services\SaveServicePackageRequest;
use App\Models\ServicePackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** The catalog of custom-work packages customers choose from when they send a brief. */
class ServicePackageController extends Controller
{
    public function index(): View
    {
        return view('erp.services.packages.index', ['packages' => ServicePackage::withCount('orders')->orderBy('sort_order')->orderBy('starting_price')->get()]);
    }

    public function create(): View
    {
        $this->authorize('sales.manage');

        return view('erp.services.packages.create', ['package' => new ServicePackage(['included_revisions' => 2, 'is_active' => true])]);
    }

    public function store(SaveServicePackageRequest $request): RedirectResponse
    {
        ServicePackage::create($this->attributes($request));

        return redirect()->route('services.packages.index')->with('status', 'Package created.');
    }

    public function edit(ServicePackage $package): View
    {
        $this->authorize('sales.manage');

        return view('erp.services.packages.edit', ['package' => $package]);
    }

    public function update(SaveServicePackageRequest $request, ServicePackage $package): RedirectResponse
    {
        $package->update($this->attributes($request));

        return redirect()->route('services.packages.index')->with('status', 'Package updated.');
    }

    public function destroy(ServicePackage $package): RedirectResponse
    {
        $this->authorize('sales.manage');

        if ($package->orders()->exists()) {
            return back()->with('error', 'This package has orders. Mark it inactive instead of deleting it.');
        }

        $package->delete();

        return redirect()->route('services.packages.index')->with('status', 'Package deleted.');
    }

    /** @return array<string, mixed> */
    private function attributes(SaveServicePackageRequest $request): array
    {
        return [
            ...$request->safe()->except(['addons_text', 'timelines_text', 'is_active', 'sort_order']),
            'addons' => ServicePackage::parseAddons($request->input('addons_text')) ?: null,
            'timelines' => ServicePackage::parseTimelines($request->input('timelines_text')) ?: null,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) $request->input('sort_order', 0),
        ];
    }
}
