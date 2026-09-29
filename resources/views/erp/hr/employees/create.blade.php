<x-layouts.erp title="Add Employee">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg p-6">
            <form action="{{ route('hr.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('erp.hr.employees._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
