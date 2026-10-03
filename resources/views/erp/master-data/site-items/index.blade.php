<x-layouts.erp title="Master Data — Website">
    <div class="max-w-6xl">
        <x-erp.flash />

        <p class="mb-4 text-sm text-gray-500">
            Kelola isi situs publik perusahaan yang berulang: kartu layanan, training, statistik, timeline, testimoni, portofolio, dan link footer. Item yang dinonaktifkan tidak tampil di situs.
        </p>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            @include('erp.master-data._nav', ['active' => 'site-list:'.$type])

            <div class="lg:col-span-3 space-y-4">
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800">Website — {{ $definition['label'] }}</h2>
                        <p class="text-sm text-gray-500">{{ $definition['description'] }} Shown in the order below (lowest number first).</p>
                        @if ($type === 'portfolio')
                            <p class="mt-1 text-sm text-gray-500">Filter categories are managed in the <a href="{{ route('masterdata.index', ['group' => 'portfolio_category']) }}" class="text-brand hover:text-brand-dark font-medium">Portfolio Category</a> dropdown.</p>
                        @endif
                    </div>
                    <a href="{{ route('masterdata.site.items.create', $type) }}" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">Add {{ $definition['singular'] }}</a>
                </div>

                <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                @if (isset($definition['fields']['image']))
                                    <th class="px-4 py-3 text-left font-medium text-gray-500 w-20">Image</th>
                                @endif
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ $definition['fields']['title']['label'] }}</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Details</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 w-20">Order</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($items as $item)
                                <tr>
                                    @if (isset($definition['fields']['image']))
                                        <td class="px-4 py-2">
                                            @if ($item->imageUrl())
                                                <img src="{{ $item->imageUrl() }}" alt="" class="h-12 w-12 rounded-md border border-gray-200 object-cover">
                                            @endif
                                        </td>
                                    @endif
                                    <td class="px-4 py-2 font-medium text-gray-800">
                                        @if ($item->icon) <span class="mr-1">{{ $item->icon }}</span> @endif
                                        {{ $item->title }}
                                        @if ($item->subtitle) <span class="block text-xs font-normal text-gray-500">{{ $item->subtitle }}</span> @endif
                                    </td>
                                    <td class="px-4 py-2 text-gray-500">
                                        @if ($item->number !== null) {{ number_format($item->number) }} @endif
                                        @if ($item->category) <span class="mr-1 rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600">{{ \App\Services\MasterData::label('portfolio_category', $item->category) }}</span> @endif
                                        {{ \Illuminate\Support\Str::limit($item->body ?? $item->link_url, 90) }}
                                    </td>
                                    <td class="px-4 py-2 text-gray-500">{{ $item->sort_order }}</td>
                                    <td class="px-4 py-2">
                                        <span @class([
                                            'rounded-full px-2 py-0.5 text-xs font-medium',
                                            'bg-green-100 text-green-700' => $item->is_active,
                                            'bg-gray-100 text-gray-500' => ! $item->is_active,
                                        ])>{{ $item->is_active ? 'Shown' : 'Hidden' }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-right whitespace-nowrap">
                                        <a href="{{ route('masterdata.site.items.edit', [$type, $item]) }}" class="action-tip mx-0.5 inline-flex items-center justify-center rounded-md p-1.5 align-middle transition text-brand hover:bg-brand-50 hover:text-brand-dark" data-tip="Edit" aria-label="Edit"><x-erp.action-icon name="edit" /></a>
                                        <form action="{{ route('masterdata.site.items.destroy', [$type, $item]) }}" method="POST" class="inline" onsubmit="return confirm('Delete this item?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-tip mx-0.5 inline-flex items-center justify-center rounded-md p-1.5 align-middle transition text-gray-400 hover:bg-red-50 hover:text-red-600" data-tip="Delete" aria-label="Delete"><x-erp.action-icon name="delete" /></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">Nothing here yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-layouts.erp>
