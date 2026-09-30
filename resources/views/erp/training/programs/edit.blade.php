<x-layouts.erp title="Edit Program">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg p-6">
            <form action="{{ route('training.programs.update', $program) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('erp.training.programs._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
