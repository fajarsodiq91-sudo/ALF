<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SaveOperatingHoursRequest;
use App\Models\Setting;
use App\Services\OperatingHours;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function edit(): View
    {
        return view('erp.settings.system', [
            'values' => Setting::values(),
            'schedule' => OperatingHours::schedule(),
            'enforced' => OperatingHours::enforced(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_npwp' => ['nullable', 'string', 'max:50'],
        ]);

        Setting::put($data);

        return redirect()->route('settings.system')->with('status', 'Settings saved.');
    }

    public function updateOperatingHours(SaveOperatingHoursRequest $request): RedirectResponse
    {
        OperatingHours::save($request->input('hours', []), $request->boolean('enforced'));

        return redirect()->route('settings.system')->with('status', 'Operating hours saved.');
    }
}
