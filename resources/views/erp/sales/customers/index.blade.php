<x-layouts.erp title="Sales — Customers">
    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">
                Daftar customer PT Alfajar Logic Futura.
                <span class="font-medium text-gray-800">{{ $customers->count() }}</span> customer.
            </p>
            @can('sales.manage')
                <a href="{{ route('sales.create') }}" class="inline-flex items-center rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark">
                    Add Customer
                </a>
            @endcan
        </div>

        <form method="GET" action="{{ route('sales.index') }}" class="mb-4 flex flex-wrap gap-3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name, contact, or email"
                   class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <select name="type" class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                <option value="">All types</option>
                @foreach (\App\Models\Customer::TYPES as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                <option value="">All statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
            <button type="submit" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Filter</button>
        </form>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Type</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Contact Person</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Email</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Phone</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">City</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        @can('sales.manage')
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($customers as $customer)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $customer->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ \App\Models\Customer::TYPES[$customer->customer_type] ?? $customer->customer_type }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $customer->contact_person ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $customer->email ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $customer->phone ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $customer->city ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($customer->is_active)
                                    <span class="inline-flex rounded-full bg-green-50 text-green-700 px-2 py-0.5 text-xs font-medium">Active</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 text-gray-500 px-2 py-0.5 text-xs font-medium">Inactive</span>
                                @endif
                            </td>
                            @can('sales.manage')
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('sales.edit', $customer) }}" class="text-brand hover:text-brand-dark font-medium">Edit</a>
                                    <form action="{{ route('sales.destroy', $customer) }}" method="POST" class="inline" onsubmit="return confirm('Delete this customer?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-gray-400 hover:text-red-600 font-medium">Delete</button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-gray-400">No customers yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.erp>
