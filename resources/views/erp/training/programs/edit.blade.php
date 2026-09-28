<x-layouts.erp title="Edit Program">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('training.programs.update', $program) }}" method="POST">
                @csrf
                @method('PUT')
                @include('erp.training.programs._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
