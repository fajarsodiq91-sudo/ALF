<x-layouts.erp title="Edit Loan">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('finance.loans.update', $loan) }}" method="POST">
                @csrf
                @method('PUT')
                @include('erp.finance.loans._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
