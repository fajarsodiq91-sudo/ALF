<x-layouts.erp title="Add Program">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('training.programs.store') }}" method="POST">
                @csrf
                @include('erp.training.programs._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
