<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $pageTitle ?? 'Customer Registration' }} | PT Alfajar Logic Futura</title>
        <link rel="icon" type="image/png" href="{{ asset('assets/icons/alf.png') }}" />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 min-h-screen bg-gradient-to-br from-steel-100 via-white to-brand-50">
        @include('erp.partials.operating-hours')
        <script>
            function registrationForm(initial, prices, counts) {
                return {
                    programs: initial.map(p => ({ payment_plan: 'full', ...p })),
                    prices,
                    counts,
                    countOf(program) { return parseInt(this.counts[program.training_program_id]) || 0; },
                    middleOf(program) { return Math.max(1, Math.ceil(this.countOf(program) / 2)); },
                    /** Shows exactly as many date rows as the chosen program has meetings. */
                    resize(program) {
                        const n = this.countOf(program);
                        while (program.meetings.length < n) { program.meetings.push({ meeting_date: '', start_time: '', end_time: '' }); }
                        program.meetings.splice(n);
                    },
                    priceOf(program) { return parseFloat(this.prices[program.training_program_id]) || 0; },
                    rupiah(amount) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount); },
                    half(program) { return Math.round(this.priceOf(program) / 2 * 100) / 100; },
                    totalFee() { return this.programs.reduce((sum, p) => sum + this.priceOf(p), 0); },
                    dueNow() { return this.programs.reduce((sum, p) => sum + (p.payment_plan === 'installment' ? this.half(p) : this.priceOf(p)), 0); },
                    hours: window.operatingHours,
                    today: new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10),
                    addProgram() { this.programs.push({ training_program_id: '', payment_plan: 'full', meetings: [] }); },
                };
            }
        </script>
        <div class="mx-auto max-w-xl px-4 py-8">
            <div class="mb-6 flex items-center justify-center gap-3">
                <img src="{{ asset('assets/icons/alf.png') }}" alt="" class="h-10 w-10 rounded">
                <span class="text-base font-semibold text-steel-800">PT Alfajar Logic Futura</span>
            </div>
            <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-6">
                <h1 class="text-lg font-semibold text-gray-800">Customer Registration</h1>
                <p class="mt-1 text-sm text-gray-500">Please fill in your details. Registration type: <span class="font-medium text-gray-700">{{ \App\Services\MasterData::label('customer_type', $customer->customer_type) }}</span>.</p>

                <form action="{{ route('customer-registration.store', $token) }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4" x-data='registrationForm(@json(old("programs", [])), @json($prices), @json($meetingCounts))'>
                    @csrf
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Name / Company Name <span class="text-red-600">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email <span class="text-red-600">*</span></label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone / WhatsApp <span class="text-red-600">*</span></label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                    @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                    <input type="text" name="city" id="city" value="{{ old('city') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                    @error('city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                    <textarea name="address" id="address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('address') }}</textarea>
                    @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <h2 class="text-sm font-semibold text-gray-800">Programs you would like to take <span class="font-normal text-gray-500">(optional)</span></h2>
                    <p class="mt-1 text-xs text-gray-500">
                        Choose a program and the dates and time slots that suit you. Our team will confirm the final schedule when they approve your registration.
                    </p>

                    @if ($errors->has('programs') || collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'programs.')))
                        <ul class="mt-3 list-disc rounded-md border border-red-200 bg-red-50 py-2 pl-8 pr-3 text-sm text-red-700">
                            @foreach (collect($errors->keys())->filter(fn ($key) => str_starts_with($key, 'programs'))->flatMap(fn ($key) => $errors->get($key))->unique() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <template x-for="(program, i) in programs" :key="i">
                        <div class="mt-3 rounded-md border border-gray-200 bg-white p-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500" x-text="'Program ' + (i + 1)"></span>
                                <button type="button" @click="programs.splice(i, 1)" class="text-xs text-gray-400 hover:text-red-600">Remove</button>
                            </div>
                            <select :name="`programs[${i}][training_program_id]`" x-model="program.training_program_id" @change="resize(program)" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                <option value="">Select a program</option>
                                @foreach ($programs as $type => $group)
                                    <optgroup label="{{ \App\Services\MasterData::label('program_type', $type) }}">
                                        @foreach ($group as $catalogProgram)
                                            <option value="{{ $catalogProgram->id }}">{{ $catalogProgram->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>

                            <div x-show="priceOf(program) > 0" x-cloak class="mt-3 rounded-md bg-brand-50 px-3 py-3 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Program fee</span>
                                    <span class="font-semibold text-gray-900" x-text="rupiah(priceOf(program))"></span>
                                </div>
                                <p class="mt-3 text-xs font-medium text-gray-600">How would you like to pay?</p>
                                <label class="mt-1 flex items-start gap-2 text-gray-700">
                                    <input type="radio" :name="`programs[${i}][payment_plan]`" value="full" x-model="program.payment_plan" class="mt-1 text-brand focus:ring-brand">
                                    <span><span class="font-medium">Pay in full upfront</span><span class="block text-xs text-gray-500" x-text="rupiah(priceOf(program)) + ' when you register'"></span></span>
                                </label>
                                <label class="mt-2 flex items-start gap-2 text-gray-700">
                                    <input type="radio" :name="`programs[${i}][payment_plan]`" value="installment" x-model="program.payment_plan" class="mt-1 text-brand focus:ring-brand">
                                    <span><span class="font-medium">50% upfront, 50% at the middle meeting</span><span class="block text-xs text-gray-500" x-text="rupiah(half(program)) + ' when you register, then ' + rupiah(priceOf(program) - half(program)) + (countOf(program) ? ' at meeting ' + middleOf(program) + ' (the middle of your ' + countOf(program) + ' meetings)' : ' at the middle meeting of your program')"></span></span>
                                </label>
                            </div>
                            <template x-if="priceOf(program) <= 0">
                                <input type="hidden" :name="`programs[${i}][payment_plan]`" value="full">
                            </template>

                            <p x-show="countOf(program)" x-cloak class="mt-3 text-xs font-medium text-gray-600">
                                This program has <span x-text="countOf(program)"></span> meeting(s). Choose a date and time slot for each one.
                            </p>
                            <div class="mt-2 space-y-2">
                                <template x-for="(meeting, j) in program.meetings" :key="j">
                                    <div class="grid grid-cols-2 gap-2 items-start rounded-md bg-gray-50 p-2">
                                        <p class="col-span-2 text-xs font-semibold text-gray-500" x-text="'Meeting ' + (j + 1) + ' of ' + program.meetings.length"></p>
                                        <div>
                                            <label class="block text-xs text-gray-500">Date</label>
                                            <input type="date" required :min="today" :name="`programs[${i}][meetings][${j}][meeting_date]`" x-model="meeting.meeting_date" @change="syncSlot(meeting)" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                            <p x-show="hoursHint(meeting.meeting_date)" x-text="hoursHint(meeting.meeting_date)" x-cloak class="mt-1 text-xs text-red-600"></p>
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500">Time slot</label>
                                            <select @change="pickSlot(meeting, $event.target.value)" :required="hours.enforced" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                                <option value="">Select slot</option>
                                                <template x-for="slot in slotsFor(meeting.meeting_date)" :key="slot.value">
                                                    <option :value="slot.value" :selected="slot.start === meeting.start_time && slot.end === meeting.end_time" x-text="slot.label"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div x-show="!hours.enforced">
                                            <label class="block text-xs text-gray-500">Start</label>
                                            <input type="time" :name="`programs[${i}][meetings][${j}][start_time]`" x-model="meeting.start_time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                        </div>
                                        <div x-show="!hours.enforced">
                                            <label class="block text-xs text-gray-500">End</label>
                                            <input type="time" :name="`programs[${i}][meetings][${j}][end_time]`" x-model="meeting.end_time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                        </div>
                                        <template x-if="hours.enforced">
                                            <div class="hidden">
                                                <input type="hidden" :name="`programs[${i}][meetings][${j}][start_time]`" :value="meeting.start_time">
                                                <input type="hidden" :name="`programs[${i}][meetings][${j}][end_time]`" :value="meeting.end_time">
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div x-show="programs.length && totalFee() > 0" x-cloak class="mt-3 rounded-md border border-brand/30 bg-white px-3 py-2 text-sm">
                        <div class="flex justify-between"><span class="text-gray-600">Total program fee</span><span class="font-semibold" x-text="rupiah(totalFee())"></span></div>
                        <div class="flex justify-between"><span class="text-gray-600">To pay when you register</span><span class="font-semibold text-brand" x-text="rupiah(dueNow())"></span></div>
                        <p class="mt-1 text-xs text-gray-400">These are standard prices. Our team confirms the final fee and payment details when approving your registration.</p>
                    </div>

                    <button type="button" @click="addProgram()" class="mt-3 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">+ Choose a program</button>
                    <p class="mt-3 text-xs text-gray-500">
                        Available days:
                        @foreach (\App\Services\OperatingHours::formatted() as $day => $slots)
                            <span class="whitespace-nowrap"><span class="font-medium text-gray-600">{{ $day }}</span> {{ $slots }}@if (! $loop->last);@endif</span>
                        @endforeach
                    </p>
                </div>

                <div>
                    <label for="photo" class="block text-sm font-medium text-gray-700">Photo</label>
                    <input type="file" name="photo" id="photo" accept="image/png,image/jpeg,image/webp"
                           class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand hover:file:bg-red-100">
                    <p class="mt-1 text-xs text-gray-500">JPG, PNG, or WebP, max 2 MB. Optional.</p>
                    @error('photo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                    <button type="submit" class="w-full rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-brand-dark hover:to-brand-dark hover:shadow-md transition-all duration-150">Submit</button>
                </form>
            </div>
        </div>
    </body>
</html>
