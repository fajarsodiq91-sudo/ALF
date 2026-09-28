<x-layouts.erp title="Edit Project">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('projects.update', $project) }}" method="POST">
                @csrf
                @method('PUT')
                @include('erp.projects._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
