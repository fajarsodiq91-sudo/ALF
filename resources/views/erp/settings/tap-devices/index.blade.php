<x-layouts.erp title="Tap Devices">
    <div class="max-w-4xl">
        <x-erp.flash />

        @if (session('device_token'))
            <div class="mb-4 rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-medium">Copy this token into the reader now. It is shown only once.</p>
                <code class="mt-2 block break-all rounded bg-white px-3 py-2 font-mono text-xs text-gray-800 select-all">{{ session('device_token') }}</code>
            </div>
        @endif

        <p class="mb-4 text-sm text-gray-500">
            Network RFID/QR readers (ESP32 or standalone) that report taps for employee attendance and training attendance.
            Each reader sends <code class="rounded bg-gray-100 px-1">POST {{ url('/api/tap') }}</code> with <code class="rounded bg-gray-100 px-1">Authorization: Bearer &lt;token&gt;</code>
            and a JSON body <code class="rounded bg-gray-100 px-1">{"uid": "04A1B2C3"}</code> or <code class="rounded bg-gray-100 px-1">{"qr": "&lt;scanned text&gt;"}</code>.
        </p>

        <div class="mb-6 bg-white rounded-lg shadow-md border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Location</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Last seen</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($devices as $device)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $device->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $device->location ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $device->last_seen_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="px-4 py-3">
                                <span @class(['inline-flex rounded-full px-2 py-0.5 text-xs font-medium', 'bg-green-50 text-green-700' => $device->is_active, 'bg-gray-100 text-gray-500' => ! $device->is_active])>{{ $device->is_active ? 'Active' : 'Disabled' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <form action="{{ route('settings.tap-devices.update', $device) }}" method="POST" class="inline">
                                    @csrf @method('PUT')
                                    <button type="submit" class="rounded-md border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50">{{ $device->is_active ? 'Disable' : 'Enable' }}</button>
                                </form>
                                <form action="{{ route('settings.tap-devices.update', $device) }}" method="POST" class="inline" onsubmit="return confirm('Create a new token? The reader stops working until it is updated.');">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="regenerate" value="1">
                                    <button type="submit" class="rounded-md border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50">New token</button>
                                </form>
                                <form action="{{ route('settings.tap-devices.destroy', $device) }}" method="POST" class="inline" onsubmit="return confirm('Remove this device?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="rounded-md border border-red-200 bg-white px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No readers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form action="{{ route('settings.tap-devices.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end bg-white rounded-lg shadow-md border border-gray-200 p-4">
            @csrf
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="Front door reader" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                <input type="text" name="location" id="location" value="{{ old('location') }}" placeholder="Lobby / Training room" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            </div>
            <button type="submit" class="rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white shadow-sm hover:shadow-md">Add reader</button>
        </form>
    </div>
</x-layouts.erp>
