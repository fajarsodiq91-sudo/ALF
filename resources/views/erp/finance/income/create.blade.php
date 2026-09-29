<x-layouts.erp title="Record Income">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg p-6">
            <form action="{{ route('finance.income.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('erp.finance.income._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
