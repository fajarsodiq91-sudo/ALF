<x-layouts.erp title="Edit Customer">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('sales.update', $customer) }}" method="POST">
                @csrf
                @method('PUT')
                @include('erp.sales.customers._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
