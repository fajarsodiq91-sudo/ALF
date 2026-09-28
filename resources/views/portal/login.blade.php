<x-layouts.portal title="Log in">
    <div class="mx-auto max-w-md bg-white rounded-lg shadow-lg border border-gray-200 p-6">
        <h1 class="text-lg font-semibold text-gray-800">Customer Portal</h1>
        <p class="mt-1 text-sm text-gray-500">Log in with your customer ID. It was sent to you in the approval email.</p>

        <form action="{{ route('portal.login.store') }}" method="POST" class="mt-5 space-y-4">
            @csrf
            <div>
                <label for="customer_code" class="block text-sm font-medium text-gray-700">Customer ID</label>
                <input type="text" name="customer_code" id="customer_code" value="{{ old('customer_code') }}" required autofocus autocomplete="username" inputmode="numeric" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                @error('customer_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" name="password" id="password" required autocomplete="current-password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-gray-300 text-brand focus:ring-brand"> Remember me
            </label>
            <button type="submit" class="w-full rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-brand-dark hover:to-brand-dark hover:shadow-md transition-all duration-150">Log in</button>
        </form>
    </div>
</x-layouts.portal>
