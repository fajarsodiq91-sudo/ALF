@php $category ??= null; @endphp

<div class="grid grid-cols-1 gap-5">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
        <input type="text" name="name" id="name" value="{{ old('name', $category->name ?? '') }}" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
        <select name="type" id="type" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <option value="income" @selected(old('type', $category->type ?? '') === 'income')>Income</option>
            <option value="expense" @selected(old('type', $category->type ?? '') === 'expense')>Expense</option>
        </select>
        @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center gap-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" id="is_active" value="1"
               @checked(old('is_active', $category->is_active ?? true))
               class="rounded border-gray-300 text-brand focus:ring-brand">
        <label for="is_active" class="text-sm text-gray-700">Active</label>
    </div>

    <div>
        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
        <textarea name="description" id="description" rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('description', $category->description ?? '') }}</textarea>
        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="inline-flex items-center rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark">
        {{ $category ? 'Update Category' : 'Create Category' }}
    </button>
    <a href="{{ route('finance.categories') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
</div>
