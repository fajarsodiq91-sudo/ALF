<x-layouts.erp title="Sales — Customers">
    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">
                Daftar customer PT Alfajar Logic Futura.
                <span class="font-medium text-gray-800">{{ $customers->total() }}</span> customer.
            </p>
            @can('sales.manage')
                <a href="{{ route('sales.create') }}" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">
                    Add Customer
                </a>
            @endcan
        </div>

        <form method="GET" action="{{ route('sales.index') }}" class="mb-4 flex flex-wrap gap-3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search ID, name, phone, or email"
                   class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <select name="type" class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                <option value="">All types</option>
                @foreach (\App\Services\MasterData::options('customer_type', request('type')) as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                <option value="">All statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                <option value="awaiting" @selected(request('status') === 'awaiting')>Awaiting customer</option>
                <option value="pending_approval" @selected(request('status') === 'pending_approval')>Pending approval</option>
                <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
            </select>
            <button type="submit" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-all duration-150 hover:bg-gray-50 hover:shadow-md hover:-translate-y-px">Filter</button>
            <a href="{{ route('sales.id-cards', request()->query()) }}" target="_blank" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-all duration-150 hover:bg-gray-50 hover:shadow-md hover:-translate-y-px">Cetak semua ID Card</a>
        </form>

        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">ID</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Type</th>
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
                            <td class="px-4 py-3 font-mono text-gray-500">{{ $customer->customer_code ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($customer->photoUrl())
                                        <img src="{{ $customer->photoUrl() }}" alt="" class="h-8 w-8 rounded-full object-cover ring-1 ring-gray-200">
                                    @else
                                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand">{{ $customer->name ? strtoupper(mb_substr($customer->name, 0, 1)) : '?' }}</span>
                                    @endif
                                    @if ($customer->isAwaitingCustomer())
                                        <span class="italic text-gray-400">Waiting for customer to fill in</span>
                                    @else
                                        <a href="{{ route('sales.show', $customer) }}" class="font-medium text-gray-800 hover:text-brand">{{ $customer->name }}</a>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ \App\Services\MasterData::label('customer_type', $customer->customer_type) }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $customer->email ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $customer->phone ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $customer->city ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($customer->isAwaitingCustomer())
                                    <span class="inline-flex rounded-full bg-amber-50 text-amber-700 px-2 py-0.5 text-xs font-medium">Awaiting customer</span>
                                @elseif ($customer->isPendingApproval())
                                    <span class="inline-flex rounded-full bg-amber-50 text-amber-700 px-2 py-0.5 text-xs font-medium">Pending approval</span>
                                @elseif ($customer->isRejected())
                                    <span class="inline-flex rounded-full bg-red-50 text-red-700 px-2 py-0.5 text-xs font-medium">Rejected</span>
                                @elseif ($customer->is_active)
                                    <span class="inline-flex rounded-full bg-green-50 text-green-700 px-2 py-0.5 text-xs font-medium">Active</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 text-gray-500 px-2 py-0.5 text-xs font-medium">Inactive</span>
                                @endif
                            </td>
                            @can('sales.manage')
                                <td class="px-4 py-3 text-right">
                                    @if ($customer->isPendingApproval())
                                        <a href="{{ route('sales.review', $customer) }}" class="action-tip mx-0.5 inline-flex items-center justify-center rounded-md p-1.5 align-middle transition text-brand hover:bg-brand-50 hover:text-brand-dark" data-tip="Review" aria-label="Review"><x-erp.action-icon name="review" /></a>
                                    @endif
                                    @if ($customer->isAwaitingCustomer())
                                        <a href="{{ route('sales.invite.show', $customer) }}" class="action-tip mx-0.5 inline-flex items-center justify-center rounded-md p-1.5 align-middle transition text-brand hover:bg-brand-50 hover:text-brand-dark" data-tip="QR Code" aria-label="QR Code"><x-erp.action-icon name="qr" /></a>
                                    @endif
                                    @if ($customer->customer_code)
                                        <a href="{{ route('sales.id-card', $customer) }}" target="_blank" class="action-tip mx-0.5 inline-flex items-center justify-center rounded-md p-1.5 align-middle transition text-brand hover:bg-brand-50 hover:text-brand-dark" data-tip="ID Card" aria-label="ID Card"><x-erp.action-icon name="id-card" /></a>
                                    @endif
                                    <a href="{{ route('sales.edit', $customer) }}" class="action-tip mx-0.5 inline-flex items-center justify-center rounded-md p-1.5 align-middle transition text-brand hover:bg-brand-50 hover:text-brand-dark" data-tip="Edit" aria-label="Edit"><x-erp.action-icon name="edit" /></a>
                                    <form action="{{ route('sales.destroy', $customer) }}" method="POST" class="inline" onsubmit="return confirm('Delete this customer?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-tip mx-0.5 inline-flex items-center justify-center rounded-md p-1.5 align-middle transition text-gray-400 hover:bg-red-50 hover:text-red-600" data-tip="Delete" aria-label="Delete"><x-erp.action-icon name="delete" /></button>
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

        @if ($customers->hasPages())
            <div class="mt-4">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</x-layouts.erp>
