<x-layouts.erp title="Add Customer">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('sales.store') }}" method="POST">
                @csrf
                @include('erp.sales.customers._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
