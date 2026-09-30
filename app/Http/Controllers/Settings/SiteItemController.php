<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SaveSiteItemRequest;
use App\Models\SiteItem;
use App\Services\ImageCompressor;
use App\Services\MasterData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** Manages one list of repeating public-site content (service cards, testimonials, portfolio, …) from Master Data → Website. */
class SiteItemController extends Controller
{
    public function index(string $type): View
    {
        $this->authorize('masterdata.manage');

        return view('erp.master-data.site-items.index', [
            'groups' => MasterData::GROUPS,
            'type' => $type,
            'definition' => SiteItem::TYPES[$type],
            'items' => SiteItem::where('type', $type)->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function create(string $type): View
    {
        $this->authorize('masterdata.manage');

        return view('erp.master-data.site-items.form', [
            'groups' => MasterData::GROUPS,
            'type' => $type,
            'definition' => SiteItem::TYPES[$type],
            'item' => new SiteItem(['is_active' => true]),
        ]);
    }

    public function store(SaveSiteItemRequest $request, string $type): RedirectResponse
    {
        $item = new SiteItem($this->attributes($request, $type));
        $item->type = $type;
        $item->sort_order = $request->filled('sort_order')
            ? (int) $request->input('sort_order')
            : (int) SiteItem::where('type', $type)->max('sort_order') + 1;

        if ($upload = $request->file('image')) {
            $item->image_path = ImageCompressor::store($upload, 'site', 'public');
        }

        $item->save();

        return redirect()->route('masterdata.site.items.index', $type)->with('status', 'Item added.');
    }

    public function edit(string $type, SiteItem $item): View
    {
        $this->authorize('masterdata.manage');
        abort_unless($item->type === $type, 404);

        return view('erp.master-data.site-items.form', [
            'groups' => MasterData::GROUPS,
            'type' => $type,
            'definition' => SiteItem::TYPES[$type],
            'item' => $item,
        ]);
    }

    public function update(SaveSiteItemRequest $request, string $type, SiteItem $item): RedirectResponse
    {
        abort_unless($item->type === $type, 404);

        $item->fill($this->attributes($request, $type));
        $item->sort_order = (int) $request->input('sort_order', $item->sort_order);

        if ($upload = $request->file('image')) {
            $this->deleteImage($item);
            $item->image_path = ImageCompressor::store($upload, 'site', 'public');
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($item);
            $item->image_url = null;
        }

        $item->save();

        return redirect()->route('masterdata.site.items.index', $type)->with('status', 'Item updated.');
    }

    public function destroy(string $type, SiteItem $item): RedirectResponse
    {
        $this->authorize('masterdata.manage');
        abort_unless($item->type === $type, 404);

        $item->delete();

        return redirect()->route('masterdata.site.items.index', $type)->with('status', 'Item deleted.');
    }

    /**
     * The columns this type's form covers, plus the on/off switch. Fields outside the type are never taken from the request.
     *
     * @return array<string, mixed>
     */
    private function attributes(SaveSiteItemRequest $request, string $type): array
    {
        $names = array_diff(array_keys(SiteItem::TYPES[$type]['fields']), ['image']);

        return [
            ...$request->safe()->only($names),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function deleteImage(SiteItem $item): void
    {
        if ($item->image_path) {
            Storage::disk('public')->delete($item->image_path);
            $item->image_path = null;
        }
    }
}
