<x-layouts.portal title="Change password">
    <div class="mx-auto max-w-md bg-white rounded-lg shadow-lg border border-gray-200 p-6">
        <h1 class="text-lg font-semibold text-gray-800">Change your password</h1>
        @if ($forced)
            <p class="mt-2 rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-700">
                You are still using your initial password (your customer ID). Please choose a new one to continue.
            </p>
        @endif

        <form action="{{ route('portal.password.update') }}" method="POST" class="mt-5 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700">Current password</label>
                <input type="password" name="current_password" id="current_password" required autocomplete="current-password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">New password</label>
                <input type="password" name="password" id="password" required autocomplete="new-password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                <p class="mt-1 text-xs text-gray-500">At least 8 characters, and not your customer ID.</p>
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm new password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            </div>
            <div class="flex items-center gap-3">
                <button type="submit" class="rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-brand-dark hover:to-brand-dark">Save password</button>
                @unless ($forced)
                    <a href="{{ route('portal.dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
                @endunless
            </div>
        </form>
    </div>
</x-layouts.portal>
