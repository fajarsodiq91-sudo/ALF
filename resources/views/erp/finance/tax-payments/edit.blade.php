<x-layouts.erp title="Edit Tax Payment">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg p-6">
            <form action="{{ route('finance.tax-payments.update', $taxPayment) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('erp.finance.tax-payments._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
