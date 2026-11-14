<x-layouts.portal title="My Orders">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-800">My orders</h1>
            <p class="text-sm text-gray-500">Custom work we build for you, such as an Excel dashboard.</p>
        </div>
        @if ($canOrder)
            <a href="{{ route('portal.orders.create') }}" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white shadow-sm hover:from-brand-dark hover:to-brand-dark hover:shadow-md transition-all duration-150">Order custom work</a>
        @endif
    </div>

    <div class="mt-5 space-y-3">
        @forelse ($orders as $order)
            <a href="{{ route('portal.orders.show', $order) }}" class="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="font-medium text-gray-800">{{ $order->title }}</p>
                        <p class="text-xs text-gray-500">{{ $order->orderNumber() }} · {{ $order->package?->name ?? 'Custom' }} · sent {{ $order->created_at->format('d M Y') }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $order->statusStyle() }}">{{ $order->statusLabel() }}</span>
                </div>
                @if ($order->quote_total)
                    <p class="mt-2 text-sm text-gray-600">Total {{ \App\Services\SessionPaymentPlan::rupiah($order->quote_total) }}</p>
                @endif
            </a>
        @empty
            <div class="rounded-lg border border-dashed border-gray-300 bg-white px-4 py-10 text-center text-sm text-gray-500">
                You have no orders yet.
                @if ($canOrder) Need a dashboard or a spreadsheet built? <a href="{{ route('portal.orders.create') }}" class="font-medium text-brand underline">Send us a brief</a>. @endif
            </div>
        @endforelse
    </div>
</x-layouts.portal>
