<x-layouts.erp title="Edit Project">
    <div class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg p-6">
            <form action="{{ route('projects.update', $project) }}" method="POST">
                @csrf
                @method('PUT')
                @include('erp.projects._form')
            </form>
        </div>
    </div>
</x-layouts.erp>
