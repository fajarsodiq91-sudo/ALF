<x-layouts.erp title="Master Data">
    <div class="max-w-6xl">
        <x-erp.flash />

        <p class="mb-4 text-sm text-gray-500">
            Kelola pilihan dropdown yang dipakai di seluruh ERP. Pilihan yang sudah dipakai tidak bisa dihapus, tapi bisa dinonaktifkan agar tidak muncul lagi di form baru.
        </p>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            @include('erp.master-data._nav', ['active' => $group])

            <div class="lg:col-span-3 space-y-4">
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4">
                    <h2 class="text-base font-semibold text-gray-800">{{ $groups[$group]['label'] }}</h2>
                    <p class="text-sm text-gray-500">{{ $groups[$group]['description'] }}</p>
                </div>

                <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Label</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 w-24">Order</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Active</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Used by</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($items as $item)
                                @php $locked = \App\Services\MasterData::isProtected($group, $item->code); @endphp
                                <tr>
                                    <td class="px-4 py-2">
                                        <form id="update-{{ $item->id }}" action="{{ route('masterdata.update', $item) }}" method="POST" class="hidden">
                                            @csrf
                                            @method('PUT')
                                        </form>
                                        <input type="text" name="label" form="update-{{ $item->id }}" value="{{ $item->label }}" required maxlength="100"
                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                        @if ($locked)
                                            <p class="mt-1 text-xs text-gray-400">System option: can be renamed only.</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" name="sort_order" form="update-{{ $item->id }}" value="{{ $item->sort_order }}" min="0"
                                               class="block w-20 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="hidden" name="is_active" form="update-{{ $item->id }}" value="0">
                                        <input type="checkbox" name="is_active" form="update-{{ $item->id }}" value="1" @checked($item->is_active) @disabled($locked)
                                               class="rounded border-gray-300 text-brand focus:ring-brand">
                                        @if ($locked)
                                            <input type="hidden" name="is_active" form="update-{{ $item->id }}" value="1">
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right text-gray-500">{{ $usage[$item->id] }}</td>
                                    <td class="px-4 py-2 text-right whitespace-nowrap">
                                        <button type="submit" form="update-{{ $item->id }}" class="text-brand hover:text-brand-dark font-medium">Save</button>
                                        @unless ($locked)
                                            <form action="{{ route('masterdata.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Delete this option?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ml-3 text-gray-400 hover:text-red-600 font-medium">Delete</button>
                                            </form>
                                        @endunless
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No options yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <form action="{{ route('masterdata.store') }}" method="POST" class="bg-white rounded-lg shadow-md border border-gray-200 p-4 flex flex-wrap items-start gap-3">
                    @csrf
                    <input type="hidden" name="group" value="{{ $group }}">
                    <div class="flex-1 min-w-[12rem]">
                        <input type="text" name="label" value="{{ old('label') }}" placeholder="New option, e.g. Startup" required maxlength="100"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                        @error('label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">Add Option</button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.erp>
