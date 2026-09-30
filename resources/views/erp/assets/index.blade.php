<x-layouts.erp title="Assets">
    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">
                Company asset register. Total value (excluding disposed):
                <span class="font-medium text-gray-800">Rp {{ number_format($totalValue, 0, ',', '.') }}</span>
            </p>
            @can('assets.manage')
                <a href="{{ route('assets.create') }}" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">
                    Add Asset
                </a>
            @endcan
        </div>

        <form method="GET" action="{{ route('assets.index') }}" class="mb-4 flex flex-wrap gap-3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name or code"
                   class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <select name="category" class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                <option value="">All categories</option>
                @foreach (\App\Services\MasterData::options('asset_category', request('category')) as $value => $label)
                    <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                <option value="">All statuses</option>
                @foreach (\App\Models\Asset::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-all duration-150 hover:bg-gray-50 hover:shadow-md hover:-translate-y-px">Filter</button>
        </form>

        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Code</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Category</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Location</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Assigned To</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Purchase Cost</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        @can('assets.manage')
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($assets as $asset)
                        <tr>
                            <td class="px-4 py-3 text-gray-500">{{ $asset->asset_code }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $asset->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ \App\Services\MasterData::label('asset_category', $asset->category) }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $asset->location ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $asset->assigned_to ?: '—' }}</td>
                            <td class="px-4 py-3 text-right text-gray-800">Rp {{ number_format((float) $asset->purchase_cost, 0, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-green-50 text-green-700' => $asset->status === 'active',
                                    'bg-amber-50 text-amber-700' => $asset->status === 'in_repair',
                                    'bg-gray-100 text-gray-500' => $asset->status === 'disposed',
                                ])>{{ \App\Models\Asset::STATUSES[$asset->status] ?? $asset->status }}</span>
                            </td>
                            @can('assets.manage')
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('assets.edit', $asset) }}" class="text-brand hover:text-brand-dark font-medium">Edit</a>
                                    <form action="{{ route('assets.destroy', $asset) }}" method="POST" class="inline" onsubmit="return confirm('Delete this asset?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-gray-400 hover:text-red-600 font-medium">Delete</button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-gray-400">No assets yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($assets->hasPages())
            <div class="mt-4">
                {{ $assets->links() }}
            </div>
        @endif
    </div>
</x-layouts.erp>
