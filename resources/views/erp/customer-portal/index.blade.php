<x-layouts.erp title="Customer Portal">
    <div class="max-w-6xl space-y-6">
        <x-erp.flash />

        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-800">What customers see</h2>
            <p class="mt-1 text-sm text-gray-500">
                Open the portal as any approved customer to check their schedule, progress, payments, materials, and uploads. The preview is <span class="font-medium text-gray-700">read-only</span>: nothing can be changed from it.
            </p>

            <label for="portal-login-url" class="mt-4 block text-xs font-medium text-gray-500">Login page for customers</label>
            <div class="mt-1 flex gap-2">
                <input type="text" id="portal-login-url" readonly value="{{ $loginUrl }}" onclick="this.select()" class="block w-full rounded-md border-gray-300 bg-gray-50 text-sm text-gray-600 shadow-sm">
                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('portal-login-url').value); this.textContent = 'Copied'"
                        class="shrink-0 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Copy</button>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">ID</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Customer</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Programs</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Portal login</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($customers as $customer)
                        <tr>
                            <td class="px-4 py-3 font-mono text-gray-500">{{ $customer->customer_code }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $customer->name }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ $customer->sessions_count }}</td>
                            <td class="px-4 py-3">
                                @if ($customer->password === null)
                                    <span class="inline-flex rounded-full bg-gray-100 text-gray-500 px-2 py-0.5 text-xs font-medium">No login (added by staff)</span>
                                @elseif ($customer->must_change_password)
                                    <span class="inline-flex rounded-full bg-amber-50 text-amber-700 px-2 py-0.5 text-xs font-medium">Initial password not changed yet</span>
                                @else
                                    <span class="inline-flex rounded-full bg-green-50 text-green-700 px-2 py-0.5 text-xs font-medium">Active</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('customer-portal.open', $customer) }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:from-brand-dark hover:to-brand-dark hover:shadow-md transition-all">Open as customer</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No approved customers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.erp>
