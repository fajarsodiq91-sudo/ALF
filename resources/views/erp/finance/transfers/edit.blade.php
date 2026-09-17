<x-layouts.erp title="Edit Transfer">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('finance.transfers.update', $transfer) }}" method="POST">
                @csrf
                @method('PUT')
                @include('erp.finance.transfers._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
