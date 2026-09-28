<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\BlockSlotRequest;
use App\Models\BlockedSlot;
use Illuminate\Http\RedirectResponse;

/** Slots the company blocks itself (other engagements), so they appear booked. */
class BlockedSlotController extends Controller
{
    public function store(BlockSlotRequest $request): RedirectResponse
    {
        BlockedSlot::create($request->validated());

        return back()->with('status', 'Slot blocked. It now shows as booked.');
    }

    public function destroy(BlockedSlot $blockedSlot): RedirectResponse
    {
        $this->authorize('masterdata.manage');

        $blockedSlot->delete();

        return back()->with('status', 'Slot is available again.');
    }
}
