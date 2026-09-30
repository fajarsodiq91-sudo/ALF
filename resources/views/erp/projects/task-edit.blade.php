<x-layouts.erp title="Edit Task">
    <div class="max-w-3xl space-y-6">
        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
            <p class="text-xs text-gray-500">{{ $task->project->code }} — {{ $task->project->name }}</p>
            <h2 class="mb-4 text-lg font-semibold text-gray-800">Edit Task</h2>
            <form action="{{ route('projects.tasks.update', $task) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')
                @include('erp.projects._task-form')
                <div class="flex items-center gap-3">
                    <button type="submit" class="rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">Save &amp; notify assignees</button>
                    <a href="{{ route('projects.show', $task->project_id) }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.erp>
