<x-layouts.erp title="Edit Role">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('settings.roles.update', $role) }}" method="POST">
                @csrf
                @method('PUT')
                @include('erp.settings.roles._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
