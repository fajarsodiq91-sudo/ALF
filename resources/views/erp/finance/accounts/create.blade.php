<x-layouts.erp title="Add Account">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('finance.accounts.store') }}" method="POST">
                @csrf
                @include('erp.finance.accounts._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
