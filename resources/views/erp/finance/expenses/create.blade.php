<x-layouts.erp title="Record Expense">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('finance.expenses.store') }}" method="POST">
                @csrf
                @include('erp.finance.expenses._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
