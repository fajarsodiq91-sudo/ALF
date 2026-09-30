<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SaveSitePageRequest;
use App\Models\Setting;
use App\Services\ImageCompressor;
use App\Services\MasterData;
use App\Services\SiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** Edits the fixed text and images of one public-site page from Master Data → Website. */
class SitePageController extends Controller
{
    public function edit(string $page): View
    {
        $this->authorize('masterdata.manage');

        return view('erp.master-data.site-page', [
            'groups' => MasterData::GROUPS,
            'page' => $page,
            'definition' => SiteContent::PAGES[$page],
        ]);
    }

    public function update(SaveSitePageRequest $request, string $page): RedirectResponse
    {
        $values = [];

        foreach (SiteContent::fields($page) as $name => $field) {
            $key = SiteContent::key("$page.$name");

            if ($field['type'] === 'image') {
                $values[$key] = $this->imageValue($request, $name, $key);

                continue;
            }

            $text = $request->input("content.$name");
            $values[$key] = $text === null ? null : str_replace("\r\n", "\n", $text);
        }

        Setting::put($values);

        return redirect()->route('masterdata.site.page.edit', $page)->with('status', 'Website content saved.');
    }

    /** The stored path after this save: a fresh upload replaces the old file; "use default" drops it; otherwise unchanged. */
    private function imageValue(SaveSitePageRequest $request, string $name, string $key): ?string
    {
        $current = Setting::get($key);
        $upload = $request->file("images.$name");

        if (! $upload && ! $request->boolean("remove.$name")) {
            return $current;
        }

        if ($current) {
            Storage::disk('public')->delete($current);
        }

        return $upload ? ImageCompressor::store($upload, 'site', 'public') : null;
    }
}
