<x-layouts.erp title="System Settings">
    <div class="max-w-2xl">
        <x-erp.flash />

        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg p-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-4">Company profile</h2>
            <form action="{{ route('settings.system.update') }}" method="POST" class="grid grid-cols-1 gap-5">
                @csrf
                @method('PUT')

                @foreach (\App\Models\Setting::FIELDS as $key => $label)
                    <div>
                        <label for="{{ $key }}" class="block text-sm font-medium text-gray-700">{{ $label }}</label>
                        @if ($key === 'company_address')
                            <textarea name="{{ $key }}" id="{{ $key }}" rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old($key, $values[$key] ?? '') }}</textarea>
                        @else
                            <input type="{{ $key === 'company_email' ? 'email' : 'text' }}" name="{{ $key }}" id="{{ $key }}"
                                   value="{{ old($key, $values[$key] ?? '') }}" @required($key === 'company_name')
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                        @endif
                        @error($key) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endforeach

                <div>
                    <button type="submit" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">Save Settings</button>
                </div>
            </form>
        </div>

        @php
            $daysForForm = collect(\App\Services\OperatingHours::DAY_NAMES)->map(fn ($name, $iso) => collect($schedule[$iso] ?? [])->map(fn ($slot) => ['start' => $slot[0], 'end' => $slot[1]])->all())->all();
            $daysForForm = old('hours') ? array_replace(array_fill_keys(range(1, 7), []), old('hours')) : $daysForForm;
            $hoursErrors = collect($errors->keys())->filter(fn ($key) => str_starts_with($key, 'hours'))->flatMap(fn ($key) => $errors->get($key))->unique();
            $timeInput = 'rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm';
        @endphp

        <div class="mt-6 bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg p-6">
            <h2 class="text-sm font-semibold text-gray-800">Operating hours</h2>
            <p class="mt-1 text-sm text-gray-500">The days and time slots in which meetings and classes can be scheduled. Customers see these hours in their portal and in the approval email.</p>

            @if ($hoursErrors->isNotEmpty())
                <ul class="mt-3 list-disc rounded-md border border-red-200 bg-red-50 py-2 pl-8 pr-4 text-sm text-red-700">
                    @foreach ($hoursErrors as $message)<li>{{ $message }}</li>@endforeach
                </ul>
            @endif

            <form action="{{ route('settings.system.hours.update') }}" method="POST" class="mt-4 space-y-4" x-data='{ days: @json($daysForForm) }'>
                @csrf
                @method('PUT')

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
</x-layouts.erp>
