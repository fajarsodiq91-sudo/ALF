<x-layouts.erp title="Training — Programs">
    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">Katalog program pelatihan yang ditawarkan PT ALF.</p>
            @can('training.manage')
                <a href="{{ route('training.programs.create') }}" class="inline-flex items-center rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark">Add Program</a>
            @endcan
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Program</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Duration</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Standard Price</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Sessions</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        @can('training.manage')
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($programs as $program)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ $program->name }}</div>
                                @if ($program->description)
                                    <div class="text-xs text-gray-500">{{ $program->description }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ $program->duration_days }} day(s)</td>
                            <td class="px-4 py-3 text-right text-gray-800">Rp {{ number_format((float) $program->standard_price, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ $program->sessions_count }}</td>
                            <td class="px-4 py-3">
                                @if ($program->is_active)
                                    <span class="inline-flex rounded-full bg-green-50 text-green-700 px-2 py-0.5 text-xs font-medium">Active</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 text-gray-500 px-2 py-0.5 text-xs font-medium">Inactive</span>
                                @endif
                            </td>
                            @can('training.manage')
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('training.programs.edit', $program) }}" class="text-brand hover:text-brand-dark font-medium">Edit</a>
                                    <form action="{{ route('training.programs.destroy', $program) }}" method="POST" class="inline" onsubmit="return confirm('Delete this program? This only works if it has no sessions.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-gray-400 hover:text-red-600 font-medium">Delete</button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">No programs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.erp>
