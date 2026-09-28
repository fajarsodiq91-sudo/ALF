<x-layouts.erp title="Edit Payroll">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('hr.payroll.update', $payroll) }}" method="POST">
                @csrf
                @method('PUT')
                @include('erp.hr.payroll._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
