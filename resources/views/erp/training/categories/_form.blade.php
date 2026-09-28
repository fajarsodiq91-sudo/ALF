@php $category ??= null; @endphp

<div class="grid grid-cols-1 gap-5">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">Category Name</label>
        <input type="text" name="name" id="name" value="{{ old('name', $category->name ?? '') }}" required autofocus class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
        <textarea name="description" id="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('description', $category->description ?? '') }}</textarea>
        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="flex items-center gap-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', $category->is_active ?? true)) class="rounded border-gray-300 text-brand focus:ring-brand">
        <label for="is_active" class="text-sm text-gray-700">Active</label>
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">{{ $category ? 'Update Category' : 'Create Category' }}</button>
    <a href="{{ route('training.categories.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
</div>
