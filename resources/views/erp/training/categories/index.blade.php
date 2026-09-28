<x-layouts.erp title="Training — Categories">
    <div class="max-w-4xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">Kelompokkan program pelatihan (mis. Excel Basic &rarr; Data Analyst) agar customer mudah memilih.</p>
            @can('training.manage')
                <a href="{{ route('training.categories.create') }}" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">Add Category</a>
            @endcan
        </div>

        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Category</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Programs</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        @can('training.manage')
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($categories as $category)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ $category->name }}</div>
                                @if ($category->description)
                                    <div class="text-xs text-gray-500">{{ $category->description }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ $category->programs_count }}</td>
                            <td class="px-4 py-3">
                                @if ($category->is_active)
                                    <span class="inline-flex rounded-full bg-green-50 text-green-700 px-2 py-0.5 text-xs font-medium">Active</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 text-gray-500 px-2 py-0.5 text-xs font-medium">Inactive</span>
                                @endif
                            </td>
                            @can('training.manage')
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('training.categories.edit', $category) }}" class="text-brand hover:text-brand-dark font-medium">Edit</a>
                                    <form action="{{ route('training.categories.destroy', $category) }}" method="POST" class="inline" onsubmit="return confirm('Delete this category? This only works if no programs are assigned to it.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-gray-400 hover:text-red-600 font-medium">Delete</button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No categories yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.erp>
