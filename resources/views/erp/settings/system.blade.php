<x-layouts.erp title="System Settings">
    <div class="max-w-2xl">
        <x-erp.flash />

        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg p-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-4">Company profile</h2>
            <form action="{{ route('settings.system.update') }}" method="POST" class="grid grid-cols-1 gap-5">
                @csrf
                @method('PUT')

                @foreach (\App\Models\Setting::FIELDS as $key => $label)
                    @if ($key === 'certificate_signer_name')
                        <div class="border-t border-gray-100 pt-5">
                            <p class="text-xs text-gray-500">Used to sign a learning certificate when its session has no instructor assigned.</p>
                        </div>
                    @endif
                    <div>
                        <label for="{{ $key }}" class="block text-sm font-medium text-gray-700">{{ $label }}</label>
                        @if (in_array($key, ['company_address', 'company_bank_account']))
                            <textarea name="{{ $key }}" id="{{ $key }}" rows="3" @if ($key === 'company_bank_account') placeholder="BCA 1234567890&#10;a.n. PT Alfajar Logic Futura" @endif
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old($key, $values[$key] ?? '') }}</textarea>
                        @else
                            <input type="{{ $key === 'company_email' ? 'email' : 'text' }}" name="{{ $key }}" id="{{ $key }}"
                                   value="{{ old($key, $values[$key] ?? '') }}" @required($key === 'company_name')
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                        @endif
                        @error($key) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endforeach

                <div class="border-t border-gray-100 pt-5">
                    <label for="default_income_tax_id" class="block text-sm font-medium text-gray-700">Default income tax</label>
                    <select name="default_income_tax_id" id="default_income_tax_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                        <option value="">No automatic tax</option>
                        @foreach (\App\Models\Tax::where('is_active', true)->orderBy('type')->orderBy('name')->get() as $tax)
                            <option value="{{ $tax->id }}" @selected(old('default_income_tax_id', $values['default_income_tax_id'] ?? '') == $tax->id)>
                                {{ $tax->name }} — {{ ['vat' => '+', 'final' => ''][$tax->type] ?? '−' }}{{ rtrim(rtrim($tax->rate, '0'), '.') }}%
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Applied automatically to every new income, including customer training payments. It can still be changed per income entry.</p>
                    @error('default_income_tax_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <button type="submit" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.erp>
