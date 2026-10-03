<x-layouts.erp title="Roles">
    <div class="max-w-4xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">Roles bundle permissions. Super Admin always has every permission.</p>
            <a href="{{ route('settings.roles.create') }}" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">Add Role</a>
        </div>

        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Role</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Permissions</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Users</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($roles as $role)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-800">
                                {{ $role->name }}
                                @if (in_array($role->name, $builtIn, true))
                                    <span class="ml-2 inline-flex rounded-full bg-gray-100 text-gray-500 px-2 py-0.5 text-xs">Built-in</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-gray-600">{{ $role->name === 'Super Admin' ? 'All' : $role->permissions_count }}</td>
                            <td class="px-4 py-3 text-right text-gray-600">{{ $role->users_count }}</td>
                            <td class="px-4 py-3 text-right">
                                @if ($role->name !== 'Super Admin')
                                    <a href="{{ route('settings.roles.edit', $role) }}" class="action-tip mx-0.5 inline-flex items-center justify-center rounded-md p-1.5 align-middle transition text-brand hover:bg-brand-50 hover:text-brand-dark" data-tip="Edit" aria-label="Edit"><x-erp.action-icon name="edit" /></a>
                                @endif
                                @unless (in_array($role->name, $builtIn, true))
                                    <form action="{{ route('settings.roles.destroy', $role) }}" method="POST" class="inline" onsubmit="return confirm('Delete this role?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-tip mx-0.5 inline-flex items-center justify-center rounded-md p-1.5 align-middle transition text-gray-400 hover:bg-red-50 hover:text-red-600" data-tip="Delete" aria-label="Delete"><x-erp.action-icon name="delete" /></button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.erp>
