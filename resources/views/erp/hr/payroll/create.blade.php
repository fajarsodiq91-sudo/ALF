<x-layouts.erp title="New Payroll">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg p-6">
            <form action="{{ route('hr.payroll.store') }}" method="POST">
                @csrf
                @include('erp.hr.payroll._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
