<x-layouts.erp title="Edit Expense">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('finance.expenses.update', $transaction) }}" method="POST">
                @csrf
                @method('PUT')
                @include('erp.finance.expenses._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
