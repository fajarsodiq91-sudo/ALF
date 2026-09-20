<x-layouts.erp title="Edit User">
    <div class="max-w-xl">
        <x-erp.flash />
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('settings.users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')
                @include('erp.settings.users._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
