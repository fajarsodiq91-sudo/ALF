<x-layouts.erp title="Services: Orders">
    <div class="max-w-6xl">
        <x-erp.flash />

        <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600">Search</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Title, customer name or ID" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600">Status</label>
                <select name="status" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                    <option value="">All</option>
                    @foreach (\App\Models\ServiceOrder::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Filter</button>
            <p class="ml-auto text-xs text-gray-500">Customers send briefs from their portal or through a "custom work" QR invitation (Sales → Add Customer).</p>
        </form>

        <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Order</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Customer</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Package</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Quote</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ $order->title }}</div>
                                <div class="text-xs text-gray-500">{{ $order->orderNumber() }} · {{ $order->created_at->format('d M Y') }}@if ($order->deadline) · deadline {{ $order->deadline->format('d M Y') }}@endif</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $order->customer->name }} <span class="font-mono text-xs text-gray-400">{{ $order->customer->customer_code }}</span></td>
                            <td class="px-4 py-3 text-gray-500">{{ $order->package?->name ?? 'Custom' }}</td>
                            <td class="px-4 py-3 text-right text-gray-800">{{ $order->quote_total ? \App\Services\SessionPaymentPlan::rupiah($order->quote_total) : '—' }}</td>
                            <td class="px-4 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $order->statusStyle() }}">{{ $order->statusLabel() }}</span></td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('services.orders.show', $order) }}" class="text-sm font-medium text-brand hover:text-brand-dark">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">No orders found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $orders->links() }}</div>
    </div>
</x-layouts.erp>
