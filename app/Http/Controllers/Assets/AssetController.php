<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assets\StoreAssetRequest;
use App\Http\Requests\Assets\UpdateAssetRequest;
use App\Models\Asset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(Request $request): View
    {
        $assets = Asset::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(fn ($inner) => $inner->where('name', 'like', $term)->orWhere('asset_code', 'like', $term));
            })
            ->orderBy('asset_code')
            ->get();

        $totalValue = $assets->where('status', '!=', 'disposed')->sum('purchase_cost');

        return view('erp.assets.index', [
            'assets' => $assets,
            'totalValue' => (float) $totalValue,
        ]);
    }

    public function create(): View
    {
        $this->authorize('assets.manage');

        return view('erp.assets.create');
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        Asset::create($request->validated());

        return redirect()
            ->route('assets.index')
            ->with('status', 'Asset created successfully.');
    }

    public function edit(Asset $asset): View
    {
        $this->authorize('assets.manage');

        return view('erp.assets.edit', ['asset' => $asset]);
    }

    public function update(UpdateAssetRequest $request, Asset $asset): RedirectResponse
    {
        $asset->update($request->validated());

        return redirect()
            ->route('assets.index')
            ->with('status', 'Asset updated successfully.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        $this->authorize('assets.manage');

        $asset->delete();

        return redirect()
            ->route('assets.index')
            ->with('status', 'Asset deleted successfully.');
    }
}
