<x-layouts.erp title="New Payroll">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('hr.payroll.store') }}" method="POST">
                @csrf
                @include('erp.hr.payroll._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
