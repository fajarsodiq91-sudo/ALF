<x-layouts.erp title="Edit Attendance">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('hr.attendance.update', $record) }}" method="POST">
                @csrf
                @method('PUT')
                @include('erp.hr.attendance._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
