<x-layouts.erp title="Add Role">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('settings.roles.store') }}" method="POST">
                @csrf
                @include('erp.settings.roles._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
