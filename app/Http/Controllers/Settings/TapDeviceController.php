<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\TapDevice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The network readers allowed to report RFID/QR taps. Each has its own secret token, shown only when created or regenerated. */
class TapDeviceController extends Controller
{
    public function index(): View
    {
        return view('erp.settings.tap-devices.index', ['devices' => TapDevice::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        [$device, $token] = TapDevice::register($data['name'], $data['location'] ?? null);

        return redirect()->route('settings.tap-devices.index')
            ->with('status', 'Device "'.$device->name.'" added.')
            ->with('device_token', $token);
    }

    public function update(Request $request, TapDevice $tapDevice): RedirectResponse
    {
        if ($request->boolean('regenerate')) {
            return redirect()->route('settings.tap-devices.index')
                ->with('status', 'New token created for "'.$tapDevice->name.'". The old one no longer works.')
                ->with('device_token', $tapDevice->regenerateToken());
        }

        $tapDevice->update(['is_active' => ! $tapDevice->is_active]);

        return redirect()->route('settings.tap-devices.index')
            ->with('status', '"'.$tapDevice->name.'" is now '.($tapDevice->is_active ? 'active' : 'disabled').'.');
    }

    public function destroy(TapDevice $tapDevice): RedirectResponse
    {
        $tapDevice->delete();

        return redirect()->route('settings.tap-devices.index')->with('status', 'Device removed.');
    }
}
