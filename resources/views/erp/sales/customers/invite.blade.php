<x-layouts.erp title="Customer Registration QR">
    <div class="max-w-xl">
        <x-erp.flash />

        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6 text-center">
            <h2 class="text-lg font-semibold text-gray-800">Registration for a new {{ \App\Models\Customer::TYPES[$customer->customer_type] ?? $customer->customer_type }} customer</h2>

            @if ($link)
                <p class="mt-1 text-sm text-gray-500">Ask the customer to scan this QR code with their phone to fill in their own details.</p>

                <div class="mx-auto mt-5 inline-block rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
                    {!! $qr !!}
                </div>

                <div class="mt-5 text-left">
                    <label for="registration-link" class="block text-xs font-medium text-gray-500">Or send this link</label>
                    <div class="mt-1 flex gap-2">
                        <input type="text" id="registration-link" readonly value="{{ $link }}" onclick="this.select()"
                               class="block w-full rounded-md border-gray-300 bg-gray-50 text-xs text-gray-600 shadow-sm">
                        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('registration-link').value); this.textContent = 'Copied'"
                                class="shrink-0 rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50">Copy</button>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Valid until {{ $customer->registration_token_expires_at->format('d M Y, H:i') }}. The link works once: it closes after the customer submits.</p>
                </div>
            @else
                <p class="mt-3 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-700">This link has expired. Generate a new one to continue.</p>
            @endif

            <div class="mt-6 flex items-center justify-center gap-4">
                <form action="{{ route('sales.invite.regenerate', $customer) }}" method="POST" onsubmit="return confirm('Generate a new link? The current QR code will stop working.');">
                    @csrf
                    <button type="submit" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Generate new link</button>
                </form>
                <a href="{{ route('sales.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Back to customers</a>
            </div>
        </div>
    </div>
</x-layouts.erp>
