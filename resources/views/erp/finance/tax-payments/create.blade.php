<x-layouts.erp title="Record Tax Payment">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('finance.tax-payments.store') }}" method="POST">
                @csrf
                @include('erp.finance.tax-payments._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
