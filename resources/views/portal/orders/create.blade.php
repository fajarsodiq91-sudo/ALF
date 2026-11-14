<x-layouts.portal title="Order custom work">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('portal.orders.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; My orders</a>
        <div class="mt-3 rounded-lg border border-gray-200 bg-white p-6 shadow-md">
            <h1 class="text-lg font-semibold text-gray-800">Order custom work</h1>
            <p class="mt-1 text-sm text-gray-500">Tell us what you need. We review your brief and send you a quote; you only pay after you accept it.</p>

            @if ($errors->any())
                <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">Please check the highlighted fields and try again.</div>
            @endif

            <form action="{{ route('portal.orders.store') }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                @include('service-orders.brief-fields', ['packages' => $packages])
                <button type="submit" class="w-full rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-brand-dark hover:to-brand-dark hover:shadow-md transition-all duration-150">Send brief</button>
            </form>
        </div>
    </div>
</x-layouts.portal>
