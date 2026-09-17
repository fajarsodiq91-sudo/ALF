<x-layouts.erp title="Edit Account">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('finance.accounts.update', $account) }}" method="POST">
                @csrf
                @method('PUT')
                @include('erp.finance.accounts._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
