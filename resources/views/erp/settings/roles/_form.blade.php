@php
    $role ??= null;
    $locked ??= false;
    $selected = old('permissions', $role ? $role->permissions->pluck('name')->all() : []);
@endphp

<div class="grid grid-cols-1 gap-5">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">Role name</label>
        <input type="text" name="name" id="name" value="{{ old('name', $role->name ?? '') }}" required @disabled($locked)
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm disabled:bg-gray-100">
        @if ($locked) <p class="mt-1 text-xs text-gray-500">Built-in roles cannot be renamed.</p> @endif
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <p class="block text-sm font-medium text-gray-700 mb-2">Permissions</p>
        <div class="space-y-4">
            @foreach ($permissions as $group => $items)
                <div class="rounded-md border border-gray-200 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">{{ $group }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach ($items as $permission)
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                       @checked(in_array($permission->name, $selected, true))
                                       class="rounded border-gray-300 text-brand focus:ring-brand">
                                {{ $permission->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        @error('permissions.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="inline-flex items-center rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark">
        {{ $role ? 'Update Role' : 'Create Role' }}
    </button>
    <a href="{{ route('settings.roles') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
</div>
