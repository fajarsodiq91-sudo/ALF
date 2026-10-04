<x-layouts.erp title="Sales — Deleted Customers">
    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">
                Customer yang sudah dihapus.
                <span class="font-medium text-gray-800">{{ $customers->total() }}</span> customer. Restore untuk mengembalikannya ke daftar customer.
            </p>
            <a href="{{ route('sales.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-all duration-150 hover:bg-gray-50 hover:shadow-md hover:-translate-y-px">Back to customers</a>
        </div>

        <form method="GET" action="{{ route('sales.deleted') }}" class="mb-4 flex flex-wrap gap-3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search ID, name, phone, or email"
                   class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <button type="submit" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-all duration-150 hover:bg-gray-50 hover:shadow-md hover:-translate-y-px">Filter</button>
        </form>

        <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">ID</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Email</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Phone</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Deleted at</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($customers as $customer)
                        <tr>
                            <td class="px-4 py-3 font-mono text-gray-500">{{ $customer->customer_code ?? '—' }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $customer->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $customer->email ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $customer->phone ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $customer->deleted_at->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3 text-right">
                                <form action="{{ route('sales.restore', $customer) }}" method="POST" class="inline" onsubmit="return confirm('Restore this customer?');">
                                    @csrf
                                    <button type="submit" class="rounded-md border border-brand px-3 py-1 text-sm font-medium text-brand transition hover:bg-brand-50">Restore</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-400">No deleted customers.</td>
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
