<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ERP Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p>{{ __('Welcome, :name.', ['name' => auth()->user()->name]) }}</p>
                    <p class="mt-2 text-sm text-gray-500">
                        {{ __('Role') }}: {{ auth()->user()->getRoleNames()->join(', ') ?: __('No role assigned') }}
                    </p>
                    <p class="mt-4 text-sm text-gray-500">
                        {{ __('The full ERP layout (sidebar, module navigation) is built in Phase 4. This page only confirms authentication and authorization are working.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
