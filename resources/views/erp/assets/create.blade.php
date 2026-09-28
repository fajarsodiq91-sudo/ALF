<x-layouts.erp title="Add Asset">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('assets.store') }}" method="POST">
                @csrf
                @include('erp.assets._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
