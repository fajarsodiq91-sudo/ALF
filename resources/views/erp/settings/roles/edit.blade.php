<x-layouts.erp title="Edit Role">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg p-6">
            <form action="{{ route('settings.roles.update', $role) }}" method="POST">
                @csrf
                @method('PUT')
                @include('erp.settings.roles._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
