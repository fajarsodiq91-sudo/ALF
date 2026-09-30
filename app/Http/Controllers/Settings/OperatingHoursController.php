<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SaveOperatingHoursRequest;
use App\Models\BlockedSlot;
use App\Services\MasterData;
use App\Services\OperatingHours;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** The company's operating hours, managed from Master Data. */
class OperatingHoursController extends Controller
{
    public function edit(): View
    {
        $this->authorize('masterdata.manage');

        return view('erp.master-data.hours', [
            'groups' => MasterData::GROUPS,
            'days' => OperatingHours::forWeekCalendar(null, true, false),
            'enforced' => OperatingHours::enforced(),
            'blocks' => BlockedSlot::where('date', '>=', today())->get()->mapWithKeys(fn (BlockedSlot $slot) => [$slot->key() => $slot->id])->all(),
        ]);
    }

    public function update(SaveOperatingHoursRequest $request): RedirectResponse
    {
        OperatingHours::save($request->input('hours', []), $request->boolean('enforced'));

        return redirect()->route('masterdata.hours.edit')->with('status', 'Operating hours saved.');
    }
}
