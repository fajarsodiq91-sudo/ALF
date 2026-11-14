<x-layouts.erp title="Add Service Package">
    <div class="max-w-2xl bg-white rounded-lg shadow-md border border-gray-200 p-6">
        <form action="{{ route('services.packages.store') }}" method="POST">
            @csrf
            @include('erp.services.packages._form')
        </form>
    </div>
</x-layouts.erp>
