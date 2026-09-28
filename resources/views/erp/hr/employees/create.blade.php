<x-layouts.erp title="Add Employee">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('hr.store') }}" method="POST">
                @csrf
                @include('erp.hr.employees._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
