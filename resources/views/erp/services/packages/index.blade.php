<x-layouts.erp title="Services: Packages">
    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">Custom-work packages (e.g. Excel dashboards) customers choose from when they send a brief. The final price always comes from the quote.</p>
            @can('sales.manage')
                <a href="{{ route('services.packages.create') }}" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">Add Package</a>
            @endcan
        </div>

        <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Package</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Starting price</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Revisions</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Days</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Orders</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        @can('sales.manage')<th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>@endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($packages as $package)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ $package->name }}</div>
                                @if ($package->description)<div class="text-xs text-gray-500">{{ $package->description }}</div>@endif
                                @if ($package->addons)<div class="text-xs text-blue-700">Add-ons: {{ collect($package->addons)->map(fn ($addon) => $addon['label'].' (Rp '.number_format($addon['price'], 0, ',', '.').')')->implode(' · ') }}</div>@endif
                            </td>
                            <td class="px-4 py-3 text-right text-gray-800">Rp {{ number_format((float) $package->starting_price, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ $package->included_revisions }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ $package->estimated_days ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ $package->orders_count }}</td>
                            <td class="px-4 py-3">
                                @if ($package->is_active)
                                    <span class="inline-flex rounded-full bg-green-50 text-green-700 px-2 py-0.5 text-xs font-medium">Active</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 text-gray-500 px-2 py-0.5 text-xs font-medium">Inactive</span>
                                @endif
                            </td>
                            @can('sales.manage')
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('services.packages.edit', $package) }}" class="action-tip mx-0.5 inline-flex items-center justify-center rounded-md p-1.5 align-middle transition text-brand hover:bg-brand-50 hover:text-brand-dark" data-tip="Edit" aria-label="Edit"><x-erp.action-icon name="edit" /></a>
                                    <form action="{{ route('services.packages.destroy', $package) }}" method="POST" class="inline" onsubmit="return confirm('Delete this package? This only works if it has no orders.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-tip mx-0.5 inline-flex items-center justify-center rounded-md p-1.5 align-middle transition text-gray-400 hover:bg-red-50 hover:text-red-600" data-tip="Delete" aria-label="Delete"><x-erp.action-icon name="delete" /></button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">No packages yet. Customers can still send a custom brief without one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.erp>
