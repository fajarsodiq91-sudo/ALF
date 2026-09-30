<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\MasterData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The agreement text customers can read from the registration form, managed from Master Data. */
class AgreementController extends Controller
{
    public function edit(): View
    {
        $this->authorize('masterdata.manage');

        return view('erp.master-data.agreement', [
            'groups' => MasterData::GROUPS,
            'content' => Setting::get('agreement_content', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('masterdata.manage');

        $data = $request->validate([
            'agreement_content' => ['nullable', 'string', 'max:65000'],
        ]);

        Setting::put(['agreement_content' => trim($data['agreement_content'] ?? '')]);

        return redirect()->route('masterdata.agreement.edit')->with('status', 'Agreement saved.');
    }
}
