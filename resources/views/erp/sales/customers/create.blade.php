<x-layouts.erp title="Add Customer">
    <div class="max-w-2xl" x-data="{ mode: '{{ old('customer_type_invite') ? 'invite' : 'self' }}' }">
        <x-erp.flash />

        <div class="mb-4 inline-flex rounded-md border border-gray-300 bg-white p-0.5 shadow-sm text-sm font-medium">
            <button type="button" @click="mode = 'self'" :class="mode === 'self' ? 'bg-gradient-to-br from-brand-light to-brand-dark text-white' : 'text-gray-600 hover:bg-gray-50'" class="rounded px-4 py-1.5 transition">I fill in the details</button>
            <button type="button" @click="mode = 'invite'" :class="mode === 'invite' ? 'bg-gradient-to-br from-brand-light to-brand-dark text-white' : 'text-gray-600 hover:bg-gray-50'" class="rounded px-4 py-1.5 transition">Customer fills in (QR code)</button>
        </div>

        <div x-show="mode === 'self'" class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
            <form action="{{ route('sales.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('erp.sales.customers._form')
            </form>
        </div>

        <div x-show="mode === 'invite'" x-cloak class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
            <p class="text-sm text-gray-600 mb-4">
                Choose only the customer type. A QR code and link will be shown for the customer to fill in their own name,
                contact details, address, and photo. The permanent customer ID is assigned once they submit.
            </p>
            <form action="{{ route('sales.invite.store') }}" method="POST">
                @csrf
                <input type="hidden" name="customer_type_invite" value="1">
                <label for="invite_customer_type" class="block text-sm font-medium text-gray-700">Type</label>
                <select name="customer_type" id="invite_customer_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                    @foreach (\App\Models\Customer::TYPES as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('customer_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                <div class="mt-6 flex items-center gap-3">
                    <button type="submit" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">Generate QR Code</button>
                    <a href="{{ route('sales.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.erp>
