<x-layouts.erp title="Master Data — Agreement">
    <div class="max-w-6xl">
        <x-erp.flash />

        <p class="mb-4 text-sm text-gray-500">
            Kelola pilihan dropdown yang dipakai di seluruh ERP, termasuk agreement untuk form pendaftaran customer.
        </p>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            @include('erp.master-data._nav', ['active' => 'agreement'])

            <div class="lg:col-span-3 space-y-4">
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4">
                    <h2 class="text-base font-semibold text-gray-800">Agreement</h2>
                    <p class="text-sm text-gray-500">Write the agreement customers agree to when they register. The registration form shows a &ldquo;Read the full agreement&rdquo; link under the checkbox that opens this text on a public page. Leave empty to hide the link. Line breaks are kept; you can update it any time.</p>
                </div>

                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4">
                    <form action="{{ route('masterdata.agreement.update') }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label for="agreement_content" class="block text-sm font-medium text-gray-700">Agreement text</label>
                            <textarea name="agreement_content" id="agreement_content" rows="20"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('agreement_content', $content) }}</textarea>
                            @error('agreement_content') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.erp>
