@php $customer ??= null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    @if ($customer && ! $customer->isAwaitingCustomer())
        <div class="sm:col-span-2">
            <span class="block text-sm font-medium text-gray-700">Customer ID</span>
            <p class="mt-1 font-mono text-sm text-gray-800">{{ $customer->customer_code }}</p>
            <p class="text-xs text-gray-500">Permanent ID for this customer, also used for repeat orders. It cannot be changed.</p>
        </div>
    @else
        <p class="sm:col-span-2 text-xs text-gray-500">A permanent customer ID (YYMMNN) is generated automatically when the customer is saved.</p>
    @endif
    <div class="sm:col-span-2">
        <label for="name" class="block text-sm font-medium text-gray-700">Customer Name</label>
        <input type="text" name="name" id="name" value="{{ old('name', $customer->name ?? '') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="customer_type" class="block text-sm font-medium text-gray-700">Type</label>
        <select name="customer_type" id="customer_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            @foreach (\App\Models\Customer::TYPES as $value => $label)
                <option value="{{ $value }}" @selected(old('customer_type', $customer->customer_type ?? 'company') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('customer_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="contact_person" class="block text-sm font-medium text-gray-700">Contact Person</label>
        <input type="text" name="contact_person" id="contact_person" value="{{ old('contact_person', $customer->contact_person ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('contact_person') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" name="email" id="email" value="{{ old('email', $customer->email ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
        <input type="text" name="phone" id="phone" value="{{ old('phone', $customer->phone ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="city" class="block text-sm font-medium text-gray-700">City</label>
        <input type="text" name="city" id="city" value="{{ old('city', $customer->city ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
        <textarea name="address" id="address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('address', $customer->address ?? '') }}</textarea>
        @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="flex items-center gap-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" id="is_active" value="1"
               @checked(old('is_active', $customer->is_active ?? true))
               class="rounded border-gray-300 text-brand focus:ring-brand">
        <label for="is_active" class="text-sm text-gray-700">Active</label>
    </div>
    <div class="sm:col-span-2">
        <label for="photo" class="block text-sm font-medium text-gray-700">Customer Photo</label>
        @if ($customer?->photoUrl())
            <div class="mt-2 flex items-center gap-4">
                <img src="{{ $customer->photoUrl() }}" alt="" class="h-16 w-16 rounded-full object-cover ring-1 ring-gray-200">
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="remove_photo" value="1" class="rounded border-gray-300 text-brand focus:ring-brand"> Remove photo
                </label>
            </div>
        @endif
        <input type="file" name="photo" id="photo" accept="image/png,image/jpeg,image/webp"
               class="mt-2 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand hover:file:bg-red-100">
        <p class="mt-1 text-xs text-gray-500">JPG, PNG, or WebP, max 2 MB.</p>
        @error('photo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
        <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('notes', $customer->notes ?? '') }}</textarea>
        @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">
        {{ $customer ? ($customer->isAwaitingCustomer() ? 'Save & Complete' : 'Update Customer') : 'Create Customer' }}
    </button>
    <a href="{{ route('sales.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
</div>
