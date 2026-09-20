<x-layouts.erp title="Add Tax">
    <div class="max-w-xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('finance.taxes.store') }}" method="POST">
                @csrf
                @include('erp.finance.taxes._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
