<x-layouts.erp title="Categories">
    <div class="max-w-4xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">Income and expense categories used when recording transactions.</p>
            @can('finance.manage')
                <a href="{{ route('finance.categories.create') }}" class="inline-flex items-center rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark">
                    Add Category
                </a>
            @endcan
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach (['income' => 'Income Categories', 'expense' => 'Expense Categories'] as $type => $heading)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-800">{{ $heading }}</h3>
                    </div>
                    <ul class="divide-y divide-gray-100">
                        @forelse ($categories->where('type', $type) as $category)
                            <li class="px-4 py-3 flex items-center justify-between text-sm">
                                <div>
                                    <span class="font-medium text-gray-800">{{ $category->name }}</span>
                                    @unless ($category->is_active)
                                        <span class="ml-2 inline-flex rounded-full bg-gray-100 text-gray-500 px-2 py-0.5 text-xs">Inactive</span>
                                    @endunless
                                </div>
                                @can('finance.manage')
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('finance.categories.edit', $category) }}" class="text-brand hover:text-brand-dark font-medium">Edit</a>
                                        <form action="{{ route('finance.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Delete this category? This only works if it has no transactions.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-gray-400 hover:text-red-600 font-medium">Delete</button>
                                        </form>
                                    </div>
                                @endcan
                            </li>
                        @empty
                            <li class="px-4 py-6 text-center text-gray-400 text-sm">No {{ $type }} categories yet.</li>
                        @endforelse
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.erp>
