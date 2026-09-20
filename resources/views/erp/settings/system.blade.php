<x-layouts.erp title="System Settings">
    <div class="max-w-2xl">
        <x-erp.flash />

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-4">Company profile</h2>
            <form action="{{ route('settings.system.update') }}" method="POST" class="grid grid-cols-1 gap-5">
                @csrf
                @method('PUT')

                @foreach (\App\Models\Setting::FIELDS as $key => $label)
                    <div>
                        <label for="{{ $key }}" class="block text-sm font-medium text-gray-700">{{ $label }}</label>
                        @if ($key === 'company_address')
                            <textarea name="{{ $key }}" id="{{ $key }}" rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old($key, $values[$key] ?? '') }}</textarea>
                        @else
                            <input type="{{ $key === 'company_email' ? 'email' : 'text' }}" name="{{ $key }}" id="{{ $key }}"
                                   value="{{ old($key, $values[$key] ?? '') }}" @required($key === 'company_name')
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                        @endif
                        @error($key) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endforeach

                <div>
                    <button type="submit" class="inline-flex items-center rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.erp>
