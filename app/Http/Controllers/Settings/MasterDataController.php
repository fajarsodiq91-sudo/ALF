<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SaveMasterDataRequest;
use App\Models\MasterDataItem;
use App\Services\MasterData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    public function index(Request $request): View
    {
        $group = array_key_exists((string) $request->input('group'), MasterData::GROUPS)
            ? $request->input('group')
            : array_key_first(MasterData::GROUPS);

        $items = MasterDataItem::where('group', $group)->orderBy('sort_order')->orderBy('label')->get();

        return view('erp.master-data.index', [
            'groups' => MasterData::GROUPS,
            'group' => $group,
            'items' => $items,
            'usage' => $items->mapWithKeys(fn (MasterDataItem $item) => [$item->id => MasterData::usageCount($group, $item->code)]),
        ]);
    }

    public function store(SaveMasterDataRequest $request): RedirectResponse
    {
        $group = $request->validated('group');
        $label = trim($request->validated('label'));

        MasterDataItem::create([
            'group' => $group,
            'code' => $this->uniqueCode($group, $label),
            'label' => $label,
            'sort_order' => $request->filled('sort_order')
                ? (int) $request->input('sort_order')
                : ((int) MasterDataItem::where('group', $group)->max('sort_order') + 1),
            'is_active' => true,
        ]);

        return redirect()->route('masterdata.index', ['group' => $group])->with('status', 'Option added.');
    }

    public function update(SaveMasterDataRequest $request, MasterDataItem $masterDataItem): RedirectResponse
    {
        $isActive = $request->boolean('is_active');

        if (! $isActive && MasterData::isProtected($masterDataItem->group, $masterDataItem->code)) {
            return redirect()->route('masterdata.index', ['group' => $masterDataItem->group])
                ->with('error', "\"{$masterDataItem->label}\" is used by system logic and cannot be deactivated.");
        }

        $masterDataItem->update([
            'label' => trim($request->validated('label')),
            'sort_order' => (int) $request->input('sort_order', $masterDataItem->sort_order),
            'is_active' => $isActive,
        ]);

        return redirect()->route('masterdata.index', ['group' => $masterDataItem->group])->with('status', 'Option updated.');
    }

    public function destroy(MasterDataItem $masterDataItem): RedirectResponse
    {
        $this->authorize('masterdata.manage');

        $back = redirect()->route('masterdata.index', ['group' => $masterDataItem->group]);

        if (MasterData::isProtected($masterDataItem->group, $masterDataItem->code)) {
            return $back->with('error', "\"{$masterDataItem->label}\" is used by system logic and cannot be deleted.");
        }

        if (($used = MasterData::usageCount($masterDataItem->group, $masterDataItem->code)) > 0) {
            return $back->with('error', "\"{$masterDataItem->label}\" is used by {$used} record(s) and cannot be deleted. Deactivate it instead.");
        }

        $masterDataItem->delete();

        return $back->with('status', 'Option deleted.');
    }

    /** The stored code never changes after creation, so renaming a label cannot break existing records. */
    private function uniqueCode(string $group, string $label): string
    {
        $base = Str::slug($label, '_') ?: 'option';
        $code = $base;

        for ($i = 2; MasterDataItem::where('group', $group)->where('code', $code)->exists(); $i++) {
            $code = $base.'_'.$i;
        }

        return $code;
    }
}
