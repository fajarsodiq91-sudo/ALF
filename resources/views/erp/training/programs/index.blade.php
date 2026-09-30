<x-layouts.erp title="Training — Programs">
    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">Katalog program pelatihan yang ditawarkan PT ALF.</p>
            @can('training.manage')
                <a href="{{ route('training.programs.create') }}" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">Add Program</a>
            @endcan
        </div>

        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Program</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Meetings</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Price</th>
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
                                <div class="flex items-start gap-2">
                                @if ($program->images->isNotEmpty())
                                    <img src="{{ $program->images->first()->url() }}" alt="" class="h-10 w-10 shrink-0 rounded-md object-cover ring-1 ring-gray-200">
                                @endif
                                <div>
                                <div class="font-medium text-gray-800">
                                    {{ $program->name }}
                                    <span class="ml-1 rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand">{{ \App\Services\MasterData::label('program_type', $program->program_type) }}</span>
                                    @if ($program->is_corporate)
                                        <span class="ml-1 rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700">Corporate</span>
                                    @endif
                                    @if ($program->category)
                                        <span class="ml-1 rounded-full bg-steel-100 px-2 py-0.5 text-xs font-medium text-steel-700">{{ $program->category->name }}</span>
                                    @endif
                                </div>
                                @if ($program->description)
                                    <div class="text-xs text-gray-500">{{ $program->description }}</div>
                                @endif
                                </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ $program->duration_days }}</td>
                            <td class="px-4 py-3 text-right">
                                @if ($program->hasActiveDiscount())
                                    <div class="text-xs text-gray-400 line-through">Rp {{ number_format((float) $program->standard_price, 0, ',', '.') }}</div>
                                    <div class="text-gray-800 font-medium">Rp {{ number_format($program->finalPrice(), 0, ',', '.') }}</div>
                                    <div class="text-xs font-medium text-green-600">{{ $program->discountLabel() }} · until {{ $program->discount_expires_at->format('d M Y') }}</div>
                                @else
                                    <span class="text-gray-800">Rp {{ number_format((float) $program->standard_price, 0, ',', '.') }}</span>
                                @endif
                                @if ($program->allowsGroups() && $program->groupTiers())
                                    <div class="mt-1 text-xs text-blue-700">Group (max {{ $program->maxGroupSize() }}): {{ collect($program->groupTiers())->map(fn ($t) => $t['min'].'+ = Rp '.number_format($t['price'], 0, ',', '.'))->implode(' · ') }}</div>
                                @endif
                            </td>
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
