<x-layouts.erp title="Edit Training Session">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('training.update', $session) }}" method="POST">
                @csrf
                @method('PUT')
                @include('erp.training.sessions._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
