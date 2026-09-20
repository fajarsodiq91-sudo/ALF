<x-layouts.erp title="Add User">
    <div class="max-w-xl">
        <x-erp.flash />
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('settings.users.store') }}" method="POST">
                @csrf
                @include('erp.settings.users._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
