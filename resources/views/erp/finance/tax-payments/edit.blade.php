<x-layouts.erp title="Edit Tax Payment">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('finance.tax-payments.update', $taxPayment) }}" method="POST">
                @csrf
                @method('PUT')
                @include('erp.finance.tax-payments._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
