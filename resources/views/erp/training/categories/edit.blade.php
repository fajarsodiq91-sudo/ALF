<x-layouts.erp title="Edit Category">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg p-6">
            <form action="{{ route('training.categories.update', $category) }}" method="POST">
                @csrf
                @method('PUT')
                @include('erp.training.categories._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
