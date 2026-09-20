<x-layouts.erp title="Taxes">
    <div class="max-w-4xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">Tax rates that can be applied to income and expense transactions. A transaction keeps the rate it was recorded with.</p>
            @can('finance.manage')
                <a href="{{ route('finance.taxes.create') }}" class="inline-flex items-center rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark">
                    Add Tax
                </a>
            @endcan
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Type</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Rate</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        @can('finance.manage')
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($taxes as $tax)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $tax->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $tax->type === 'vat' ? 'PPN / VAT' : 'PPh / Withholding' }}</td>
                            <td class="px-4 py-3 text-right text-gray-800">{{ rtrim(rtrim($tax->rate, '0'), '.') }}%</td>
                            <td class="px-4 py-3">
                                @if ($tax->is_active)
                                    <span class="inline-flex rounded-full bg-green-100 text-green-700 px-2 py-0.5 text-xs">Active</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 text-gray-500 px-2 py-0.5 text-xs">Inactive</span>
                                @endif
                            </td>
                            @can('finance.manage')
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('finance.taxes.edit', $tax) }}" class="text-brand hover:text-brand-dark font-medium">Edit</a>
                                    <form action="{{ route('finance.taxes.destroy', $tax) }}" method="POST" class="inline" onsubmit="return confirm('Delete this tax? This only works if no transaction uses it.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-gray-400 hover:text-red-600 font-medium">Delete</button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-400">No taxes defined yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.erp>
