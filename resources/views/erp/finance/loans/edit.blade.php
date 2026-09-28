<x-layouts.erp title="Edit Loan">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg p-6">
            <form action="{{ route('finance.loans.update', $loan) }}" method="POST">
                @csrf
                @method('PUT')
                @include('erp.finance.loans._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
