<x-layouts.erp title="Edit Category">
    <div class="max-w-xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('finance.categories.update', $category) }}" method="POST">
                @csrf
                @method('PUT')
                @include('erp.finance.categories._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
