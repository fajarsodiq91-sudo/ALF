<x-layouts.erp title="Edit Employee">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('hr.update', $employee) }}" method="POST">
                @csrf
                @method('PUT')
                @include('erp.hr.employees._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
