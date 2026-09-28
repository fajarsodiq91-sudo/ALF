<x-layouts.erp title="Edit Tax">
    <div class="max-w-xl">
        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg p-6">
            <form action="{{ route('finance.taxes.update', $tax) }}" method="POST">
                @csrf
                @method('PUT')
                @include('erp.finance.taxes._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
