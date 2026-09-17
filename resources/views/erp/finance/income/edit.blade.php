<x-layouts.erp title="Edit Income">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('finance.income.update', $transaction) }}" method="POST">
                @csrf
                @method('PUT')
                @include('erp.finance.income._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
