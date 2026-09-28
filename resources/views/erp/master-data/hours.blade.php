<x-layouts.erp title="Master Data — Operating Hours">
    @php
        $hoursErrors = collect($errors->keys())->filter(fn ($key) => str_starts_with($key, 'hours'))->flatMap(fn ($key) => $errors->get($key))->unique();
        $initialDays = old('hours') ? array_replace(array_fill_keys(range(1, 7), []), old('hours')) : $days;
        $timeInput = 'rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm';
    @endphp

    <div class="max-w-6xl">
        <x-erp.flash />

        <p class="mb-4 text-sm text-gray-500">
            Kelola pilihan dropdown yang dipakai di seluruh ERP, termasuk jam operasional untuk penjadwalan pertemuan.
        </p>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            @include('erp.master-data._nav', ['active' => 'operating-hours'])

            <div class="lg:col-span-3 space-y-4" x-data='{ days: @json($initialDays) }'>
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4">
                    <h2 class="text-base font-semibold text-gray-800">Operating Hours</h2>
                    <p class="text-sm text-gray-500">The days and time windows in which meetings and classes can be scheduled. Programs with a session length let customers pick any start time inside a window. Customers see them in the booking calendar and in their portal.</p>
                </div>

                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4" x-data="{ open: false }">
                    <button type="button" @click="open = !open" :aria-expanded="open" class="flex w-full items-center justify-between text-left">
                        <h3 class="text-sm font-semibold text-gray-800">Weekly schedule <span class="font-normal text-gray-400">(updates as you edit)</span></h3>
                        <span class="text-sm font-medium text-brand" x-text="open ? 'Collapse ▲' : 'Expand ▼'"></span>
                    </button>
                    <div x-show="open" x-cloak class="mt-3">
                        @include('erp.partials.week-calendar')
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4">
                    <h3 class="text-sm font-semibold text-gray-800">Block slots</h3>
                    <p class="mb-3 text-xs text-gray-500">Click a day's operating window to block the time you are busy (from–until); it will show as booked to customers. Click a grey blocked time to free it again.</p>
                    @if ($errors->has('date'))
                        <p class="mb-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ $errors->first('date') }}</p>
                    @endif
                    @include('erp.partials.availability-calendar', ['manage' => true, 'blocks' => $blocks, 'blockUrl' => route('masterdata.blocked.store'), 'unblockUrl' => route('masterdata.blocked.destroy', '__ID__')])
                </div>

                <form action="{{ route('masterdata.hours.update') }}" method="POST" class="bg-white rounded-lg shadow-md border border-gray-200 p-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <h3 class="text-sm font-semibold text-gray-800">Edit slots</h3>

                    @if ($hoursErrors->isNotEmpty())
                        <ul class="list-disc rounded-md border border-red-200 bg-red-50 py-2 pl-8 pr-4 text-sm text-red-700">
                            @foreach ($hoursErrors as $message)<li>{{ $message }}</li>@endforeach
                        </ul>
                    @endif

                    @foreach (\App\Services\OperatingHours::DAY_NAMES as $iso => $dayName)
                        <div class="rounded-md border border-gray-200 p-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-800">{{ $dayName }}</span>
                                <button type="button" @click="days[{{ $iso }}].push({ start: '', end: '' })" class="text-sm font-medium text-brand hover:text-brand-dark">+ Add slot</button>
                            </div>
                            <p x-show="days[{{ $iso }}].length === 0" class="mt-1 text-xs text-gray-400">Closed</p>
                            <div class="mt-2 space-y-2">
                                <template x-for="(slot, i) in days[{{ $iso }}]" :key="i">
                                    <div class="flex items-center gap-2">
                                        <input type="time" required :name="`hours[{{ $iso }}][${i}][start]`" x-model="slot.start" class="{{ $timeInput }}">
                                        <span class="text-gray-400">–</span>
                                        <input type="time" required :name="`hours[{{ $iso }}][${i}][end]`" x-model="slot.end" class="{{ $timeInput }}">
                                        <button type="button" @click="days[{{ $iso }}].splice(i, 1)" class="text-sm text-gray-400 hover:text-red-600">Remove</button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    @endforeach

                    <label class="flex items-start gap-2 text-sm text-gray-700">
                        <input type="hidden" name="enforced" value="0">
                        <input type="checkbox" name="enforced" value="1" @checked(old('enforced', $enforced)) class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                        <span>
                            <span class="font-medium">Only allow meetings inside these hours</span>
                            <span class="block text-xs text-gray-500">When on, a meeting must be on an open day and exactly match one of its slots. Turn off to allow exceptions.</span>
                        </span>
                    </label>

                    <button type="submit" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">Save Operating Hours</button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.erp>
