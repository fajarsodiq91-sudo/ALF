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
        @include('erp.partials.slot-picker', ['booked' => $booked])
        @php
            // After a failed submit, reopen on the step that holds the problem: the details, or the final review.
            $identityFields = ['name', 'email', 'phone', 'city', 'address', 'photo'];
            $startStep = $errors->any() && ! collect($identityFields)->contains(fn ($field) => $errors->has($field)) ? 'summary' : 'identity';
        @endphp
        <script>
            function registrationForm(initial, prices, standardPrices, discountLabels, counts, minutes, corporate, images, terms, maxGroups, registrationFees, names, startStep) {
                return {
                    programs: initial.map(p => ({ payment_plan: 'full', group_size: 1, ...p })),
                    prices, standardPrices, discountLabels,
                    counts, minutes, corporate, images, terms, maxGroups, registrationFees, names,
                    step: startStep,
                    cur: 0,
                    editing: false,
                    error: '',
                    maxPrograms: 5,
                    stepLabels: ['Your details', 'Program', 'Schedule', 'Review & pay'],
                    stepIndex() { return { identity: 0, program: 1, booking: 2, more: 2, summary: 3 }[this.step]; },
                    /** The text typed into a details field, re-read whenever the step changes (so the review shows edits). */
                    fieldValue(id) { return this.step && (document.getElementById(id)?.value ?? ''); },
                    nameOf(program) { return this.names[program.training_program_id] || 'Program'; },
                    minutesOf(program) { return parseInt(this.minutes[program.training_program_id]) || null; },
                    corporateOf(program) { return !!this.corporate[program.training_program_id]; },
                    imagesOf(program) { return this.images[program.training_program_id] || []; },
                    termsOf(program) { return this.terms[program.training_program_id] || null; },
                    anyTermsToAgree() { return this.programs.some(p => this.termsOf(p)); },
                    countOf(program) { return parseInt(this.counts[program.training_program_id]) || 0; },
                    middleOf(program) { const n = this.countOf(program); return n < 2 ? 1 : Math.floor(n / 2) + 1; },
                    installmentAllowed(program) { return this.countOf(program) !== 1 || this.minutesOf(program) === 420; },
                    /** Shows exactly as many date rows as the chosen program has meetings. */
                    resize(program) {
                        const n = this.countOf(program);
                        while (program.meetings.length < n) { program.meetings.push({ meeting_date: '', start_time: '', end_time: '' }); }
                        program.meetings.splice(n);
                        if (!this.installmentAllowed(program)) { program.payment_plan = 'full'; }
                        program.group_size = this.sizeOf(program);
                    },
                    /** A different program has a different slot length, so any slots picked for the previous one are cleared. */
                    changeProgram(program) { program.meetings = []; this.resize(program); },
                    maxGroupOf(program) { return parseInt(this.maxGroups[program.training_program_id]) || 1; },
                    sizeOf(program) { return Math.min(Math.max(parseInt(program.group_size) || 1, 1), this.maxGroupOf(program)); },
                    /** Price of one person for the chosen group size (the price steps down as more people join). */
                    perPersonOf(program) { return parseFloat((this.prices[program.training_program_id] || {})[this.sizeOf(program)]) || 0; },
                    /** What the whole group pays for this program. */
                    priceOf(program) { return this.perPersonOf(program) * this.sizeOf(program); },
                    standardPriceOf(program) { return parseFloat(this.standardPrices[program.training_program_id]) || 0; },
                    discountLabelOf(program) { return this.discountLabels[program.training_program_id] || null; },
                    hasDiscount(program) { return this.discountLabelOf(program) !== null && this.standardPriceOf(program) > this.perPersonOf(program); },
                    hasGroupRate(program) { return this.sizeOf(program) > 1; },
                    hasSaving(program) { return this.standardPriceOf(program) * this.sizeOf(program) > this.priceOf(program); },
                    rupiah(amount) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount); },
                    half(program) { return Math.round(this.priceOf(program) / 2 * 100) / 100; },
                    dueOf(program) { return program.payment_plan === 'installment' && this.installmentAllowed(program) ? this.half(program) : this.priceOf(program); },
                    totalFee() { return this.programs.reduce((sum, p) => sum + this.priceOf(p), 0); },
                    dueNow() { return this.programs.reduce((sum, p) => sum + this.dueOf(p), 0); },
                    /** The one-time registration fee follows the first program on the list. */
                    registrationFee() { const first = this.programs[0]; return first ? parseFloat(this.registrationFees[first.training_program_id]) || 0 : 0; },
                    hours: window.operatingHours,
                    attempted: false,
                    programReady(program) { return program.meetings.length > 0 && program.meetings.every(m => window.meetingReady(m)); },
                    allReady() { return this.programs.every(p => this.programReady(p)); },
                    allMeetings() { return this.programs.flatMap(p => p.meetings); },
                    addProgram() { this.programs.push({ training_program_id: '', payment_plan: 'full', group_size: 1, meetings: [] }); },

                    go(step) { this.error = ''; this.step = step; window.scrollTo({ top: 0, behavior: 'smooth' }); },
                    next() {
                        this.error = '';
                        if (this.step === 'identity') { this.finishIdentity(); }
                        else if (this.step === 'program') { this.finishProgram(); }
                        else if (this.step === 'booking') { this.finishBooking(); }
                    },
                    finishIdentity() {
                        for (const id of ['name', 'email', 'phone', 'city', 'address', 'photo']) {
                            const field = document.getElementById(id);
                            if (field && !field.reportValidity()) { return; }
                        }
                        if (this.editing) { this.toReview(); return; }
                        if (this.programs.length === 0) { this.addProgram(); }
                        this.cur = 0;
                        this.go('program');
                    },
                    finishProgram() {
                        if (!this.programs[this.cur].training_program_id) { this.error = 'Please choose a program to continue.'; return; }
                        this.go('booking');
                    },
                    finishBooking() {
                        if (!this.programReady(this.programs[this.cur])) { this.error = 'Please choose a date and time on the calendar for every meeting.'; return; }
                        if (this.editing) { this.toReview(); return; }
                        this.go('more');
                    },
                    addAnother() { this.addProgram(); this.cur = this.programs.length - 1; this.go('program'); },
                    /** Drops half-finished programs, then shows the total to pay. */
                    toReview() { this.programs = this.programs.filter(p => p.training_program_id); this.editing = false; this.go('summary'); },
                    skipPrograms() { this.programs = []; this.toReview(); },
                    edit(step, index = 0) { this.editing = true; this.cur = index; this.go(step); },
                    remove(index) { this.programs.splice(index, 1); },
                    back() {
                        if (this.editing) { this.toReview(); return; }
                        if (this.step === 'booking') { this.go('program'); }
                        else if (this.step === 'more') { this.go('booking'); }
                        else if (this.step === 'program') {
                            if (this.cur === 0) { this.go('identity'); return; }
                            if (!this.programs[this.cur].training_program_id) { this.programs.splice(this.cur, 1); }
                            this.cur = this.programs.length - 1;
                            this.go('more');
                        } else if (this.step === 'summary') {
                            if (this.programs.length === 0) { this.addProgram(); this.cur = 0; this.go('program'); return; }
                            this.cur = this.programs.length - 1;
                            this.go('more');
                        }
                    },
                    submitForm(event) {
                        if (this.step !== 'summary') { event.preventDefault(); this.next(); return; }
                        if (!this.allReady()) { event.preventDefault(); this.attempted = true; return; }
                        if (this.programs.length && !this.$refs.terms.reportValidity()) { event.preventDefault(); }
                    },
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
                <p class="mt-1 text-sm text-gray-500">Registration type: <span class="font-medium text-gray-700">{{ \App\Services\MasterData::label('customer_type', $customer->customer_type) }}</span>.</p>

                <form action="{{ route('customer-registration.store', $token) }}" method="POST" enctype="multipart/form-data" novalidate class="mt-5" @submit="submitForm($event)" x-data='registrationForm(@json(old("programs", [])), @json($prices), @json($standardPrices), @json($discountLabels), @json($meetingCounts), @json($sessionMinutes), @json($corporateFlags), @json($programImages), @json($programTerms), @json($maxGroupSizes), @json($registrationFees), @json($programNames), @json($startStep))'>
                    @csrf

                    {{-- Progress --}}
                    <ol class="mb-5 flex items-center gap-1 text-[11px] font-medium">
                        <template x-for="(label, n) in stepLabels" :key="n">
                            <li class="flex-1">
                                <div class="h-1.5 rounded-full" :class="n <= stepIndex() ? 'bg-brand' : 'bg-gray-200'"></div>
                                <span class="mt-1 block truncate" :class="n === stepIndex() ? 'text-brand' : 'text-gray-400'" x-text="(n + 1) + '. ' + label"></span>
                            </li>
                        </template>
                    </ol>

                    <p x-show="error" x-cloak x-text="error" class="mb-4 rounded-md bg-red-50 px-3 py-2 text-sm text-red-700"></p>

                    {{-- Step 1: who is registering --}}
                    <div x-show="step === 'identity'" class="space-y-4">
                        <p class="text-sm text-gray-500">Please fill in your details first. You will choose your program on the next step.</p>
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
                        <div>
                            <label for="photo" class="block text-sm font-medium text-gray-700">Photo</label>
                            <input type="file" name="photo" id="photo" accept="image/png,image/jpeg,image/webp"
                                   class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand hover:file:bg-red-100">
                            <p class="mt-1 text-xs text-gray-500">JPG, PNG, or WebP, max 2 MB. Optional.</p>
                            @error('photo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button type="button" @click="next()" class="w-full rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-brand-dark hover:to-brand-dark hover:shadow-md transition-all duration-150" x-text="editing ? 'Save & back to review' : 'Next: choose a program'"></button>
                        <button type="button" x-show="!editing" @click="skipPrograms()" class="w-full text-center text-xs text-gray-500 hover:text-gray-700 hover:underline">Skip — I will choose a program later</button>
                    </div>

                    {{-- Steps 2 and 3: one program at a time, first its details, then its calendar slots --}}
                    <template x-for="(program, i) in programs" :key="i">
                        <div>
                            {{-- Step 2: choose the program --}}
                            <div x-show="step === 'program' && cur === i" x-cloak class="space-y-3">
                                <h2 class="text-sm font-semibold text-gray-800" x-text="'Program ' + (i + 1) + ' — choose your program'"></h2>
                                <p class="text-xs text-gray-500">Our team will confirm the final schedule when they approve your registration.</p>

                                @if ($errors->has('programs') || collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'programs.')))
                                    <ul class="list-disc rounded-md border border-red-200 bg-red-50 py-2 pl-8 pr-3 text-sm text-red-700">
                                        @foreach (collect($errors->keys())->filter(fn ($key) => str_starts_with($key, 'programs'))->flatMap(fn ($key) => $errors->get($key))->unique() as $message)
                                            <li>{{ $message }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                <select :name="`programs[${i}][training_program_id]`" x-model="program.training_program_id" @change="changeProgram(program)" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                    <option value="">Select a program</option>
                                    @foreach ($programs as $categoryName => $group)
                                        <optgroup label="{{ $categoryName }}">
                                            @foreach ($group as $catalogProgram)
                                                <option value="{{ $catalogProgram->id }}">{{ $catalogProgram->name }}{{ $catalogProgram->hasActiveDiscount() ? ' — '.$catalogProgram->discountLabel() : '' }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <p x-show="corporateOf(program)" x-cloak class="text-xs font-medium text-purple-700">Corporate training — unlocks extra weekday time slots when you pick a date.</p>

                                <div x-show="imagesOf(program).length" x-cloak class="flex flex-wrap gap-2">
                                    <template x-for="(img, k) in imagesOf(program)" :key="k">
                                        <a :href="img" target="_blank" rel="noopener">
                                            <img :src="img" class="h-20 w-20 rounded-md object-cover ring-1 ring-gray-200 hover:ring-brand">
                                        </a>
                                    </template>
                                </div>

                                <div x-show="termsOf(program)" x-cloak class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2">
                                    <p class="text-xs font-semibold text-amber-800">Terms &amp; Conditions of this program</p>
                                    <p class="mt-1 whitespace-pre-line text-xs text-amber-700" x-text="termsOf(program)"></p>
                                </div>

                                <div x-show="maxGroupOf(program) > 1" x-cloak class="rounded-md border border-blue-200 bg-blue-50 px-3 py-2">
                                    <label class="block text-xs font-semibold text-blue-800">Registering together? Number of people (including you)</label>
                                    <select :name="`programs[${i}][group_size]`" x-model.number="program.group_size" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                        <template x-for="n in maxGroupOf(program)" :key="n">
                                            <option :value="n" x-text="n + (n === 1 ? ' person' : ' people') + ' — ' + rupiah((prices[program.training_program_id] || {})[n] || 0) + ' per person'"></option>
                                        </template>
                                    </select>
                                    <p class="mt-1 text-xs text-blue-700">The more people, the lower the price per person. You pay for the whole group; after approval you get a link to share so each friend joins with their own customer ID and portal login.</p>
                                </div>

                                <div x-show="priceOf(program) > 0" x-cloak class="rounded-md bg-brand-50 px-3 py-3 text-sm">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-600" x-text="hasGroupRate(program) ? 'Program fee (' + sizeOf(program) + ' people × ' + rupiah(perPersonOf(program)) + ')' : 'Program fee'"></span>
                                        <span>
                                            <span x-show="hasSaving(program)" x-cloak class="mr-2 text-xs text-gray-400 line-through" x-text="rupiah(standardPriceOf(program) * sizeOf(program))"></span>
                                            <span class="font-semibold text-gray-900" x-text="rupiah(priceOf(program))"></span>
                                        </span>
                                    </div>
                                    <p x-show="discountLabelOf(program) !== null" x-cloak class="mt-1 text-xs font-medium text-green-600" x-text="'Promo: ' + discountLabelOf(program)"></p>
                                    <p class="mt-3 text-xs font-medium text-gray-600">How would you like to pay?</p>
                                    <label class="mt-1 flex items-start gap-2 text-gray-700">
                                        <input type="radio" :name="`programs[${i}][payment_plan]`" value="full" x-model="program.payment_plan" class="mt-1 text-brand focus:ring-brand">
                                        <span><span class="font-medium">Pay in full upfront</span><span class="block text-xs text-gray-500" x-text="rupiah(priceOf(program)) + ' when you register'"></span></span>
                                    </label>
                                    <p x-show="!installmentAllowed(program)" x-cloak class="mt-1 text-xs text-gray-500">This program has a single short meeting, so payment is 100% upfront.</p>
                                    <label x-show="installmentAllowed(program)" class="mt-2 flex items-start gap-2 text-gray-700">
                                        <input type="radio" :name="`programs[${i}][payment_plan]`" value="installment" x-model="program.payment_plan" class="mt-1 text-brand focus:ring-brand">
                                        <span><span class="font-medium">50% upfront, 50% at the middle meeting (meeting 4 of 6, 7 of 12; after the training for a full-day program)</span><span class="block text-xs text-gray-500" x-text="rupiah(half(program)) + ' when you register, then ' + rupiah(priceOf(program) - half(program)) + (minutesOf(program) === 420 ? ' after the training is completed' : countOf(program) ? ' at meeting ' + middleOf(program) + ' (halfway through your ' + countOf(program) + ' meetings)' : ' at the middle meeting of your program')"></span></span>
                                    </label>
                                </div>
                                <template x-if="priceOf(program) <= 0">
                                    <input type="hidden" :name="`programs[${i}][payment_plan]`" value="full">
                                </template>

                                <div class="flex gap-2 pt-1">
                                    <button type="button" @click="back()" class="rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Back</button>
                                    <button type="button" @click="next()" class="flex-1 rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-brand-dark hover:to-brand-dark hover:shadow-md transition-all duration-150">Next: book your schedule</button>
                                </div>
                            </div>

                            {{-- Step 3: book the meetings on the calendar --}}
                            <div x-show="step === 'booking' && cur === i" x-cloak class="space-y-3">
                                <h2 class="text-sm font-semibold text-gray-800" x-text="'Program ' + (i + 1) + ' — book your schedule'"></h2>
                                <p class="text-sm font-medium text-brand" x-text="nameOf(program)"></p>
                                <p x-show="countOf(program)" x-cloak class="text-xs font-medium text-gray-600">
                                    This program has <span x-text="countOf(program)"></span> meeting(s). Choose a date and time slot for each one.
                                </p>
                                <div class="space-y-2">
                                    <template x-for="(meeting, j) in program.meetings" :key="j">
                                        <div class="grid grid-cols-2 gap-2 items-start rounded-md bg-gray-50 p-2">
                                            <p class="col-span-2 text-xs font-semibold text-gray-500" x-text="'Meeting ' + (j + 1) + ' of ' + program.meetings.length"></p>
                                            <div class="col-span-2 flex flex-wrap items-center justify-between gap-2">
                                                <div>
                                                    <p class="text-sm" :class="meeting.meeting_date ? 'font-medium text-gray-800' : 'text-gray-400'" x-text="meetingLabel(meeting)"></p>
                                                    <p x-show="hoursHint(meeting.meeting_date, corporateOf(program))" x-text="hoursHint(meeting.meeting_date, corporateOf(program))" x-cloak class="text-xs text-red-600"></p>
                                                </div>
                                                <button type="button" @click="$dispatch('open-slot-picker', { meeting, siblings: allMeetings(), minutes: minutesOf(program), corporate: corporateOf(program) })" class="shrink-0 inline-flex items-center gap-1.5 rounded-md border border-brand/40 bg-white px-3 py-1.5 text-sm font-medium text-brand shadow-sm transition hover:bg-brand-50 hover:shadow"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg> Choose on calendar</button>
                                            </div>
                                            <template x-if="hours.enforced">
                                                <div class="hidden">
                                                    <input type="hidden" :name="`programs[${i}][meetings][${j}][meeting_date]`" :value="meeting.meeting_date">
                                                    <input type="hidden" :name="`programs[${i}][meetings][${j}][start_time]`" :value="meeting.start_time">
                                                    <input type="hidden" :name="`programs[${i}][meetings][${j}][end_time]`" :value="meeting.end_time">
                                                </div>
                                            </template>
                                            <template x-if="!hours.enforced">
                                                <div class="col-span-2 grid grid-cols-3 gap-2">
                                                    <input type="date" :name="`programs[${i}][meetings][${j}][meeting_date]`" x-model="meeting.meeting_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                                    <input type="time" :name="`programs[${i}][meetings][${j}][start_time]`" x-model="meeting.start_time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                                    <input type="time" :name="`programs[${i}][meetings][${j}][end_time]`" x-model="meeting.end_time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>

                                <div>
                                    <p class="text-xs font-medium text-gray-600">Our operating hours</p>
                                    <p class="mt-1 text-[11px] text-gray-400">Choosing a corporate training program on the calendar may unlock extra weekday slots not shown here.</p>
                                    <div class="mt-2" x-data='{ days: @json(\App\Services\OperatingHours::forWeekCalendar(null, false)) }'>
                                        @include('erp.partials.week-calendar')
                                    </div>
                                </div>

                                <div class="flex gap-2 pt-1">
                                    <button type="button" @click="back()" class="rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Back</button>
                                    <button type="button" @click="next()" class="flex-1 rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-brand-dark hover:to-brand-dark hover:shadow-md transition-all duration-150" x-text="editing ? 'Save & back to review' : 'Next'"></button>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Step 4: any more programs? --}}
                    <div x-show="step === 'more'" x-cloak class="space-y-4">
                        <h2 class="text-sm font-semibold text-gray-800">Would you like to take another program?</h2>
                        <ul class="space-y-1 text-sm text-gray-700">
                            <template x-for="(program, i) in programs" :key="i">
                                <li class="flex items-center gap-2 rounded-md bg-gray-50 px-3 py-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    <span x-text="'Program ' + (i + 1) + ': ' + nameOf(program)"></span>
                                </li>
                            </template>
                        </ul>
                        <p x-show="programs.length >= maxPrograms" x-cloak class="text-xs text-gray-500">You have reached the maximum of <span x-text="maxPrograms"></span> programs per registration.</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <button type="button" x-show="programs.length < maxPrograms" @click="addAnother()" class="rounded-md border border-brand/40 bg-white px-4 py-2.5 text-sm font-semibold text-brand shadow-sm hover:bg-brand-50">Yes, add another program</button>
                            <button type="button" @click="toReview()" class="rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-brand-dark hover:to-brand-dark hover:shadow-md transition-all duration-150" :class="programs.length >= maxPrograms ? 'sm:col-span-2' : ''">No, continue to payment</button>
                        </div>
                        <button type="button" @click="back()" class="text-sm text-gray-500 hover:text-gray-700 hover:underline">Back</button>
                    </div>

                    {{-- Step 5: total to pay, terms, submit --}}
                    <div x-show="step === 'summary'" x-cloak class="space-y-4">
                        <h2 class="text-sm font-semibold text-gray-800">Review &amp; payment</h2>

                        @if ($errors->any())
                            <ul class="list-disc rounded-md border border-red-200 bg-red-50 py-2 pl-8 pr-3 text-sm text-red-700">
                                @foreach ($errors->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Your details</p>
                                    <p class="mt-1 text-gray-800" x-text="fieldValue('name')"></p>
                                    <p class="text-xs text-gray-500" x-text="fieldValue('email') + ' · ' + fieldValue('phone')"></p>
                                </div>
                                <button type="button" @click="edit('identity')" class="text-xs font-medium text-brand hover:underline">Edit</button>
                            </div>
                        </div>

                        <p x-show="programs.length === 0" x-cloak class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600">You have not chosen a program. You can still register now, and our team will contact you.</p>

                        <template x-for="(program, i) in programs" :key="i">
                            <div class="rounded-md border border-gray-200 bg-white px-3 py-2 text-sm">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500" x-text="'Program ' + (i + 1)"></p>
                                        <p class="mt-1 font-medium text-gray-800" x-text="nameOf(program) + (hasGroupRate(program) ? ' (' + sizeOf(program) + ' people)' : '')"></p>
                                    </div>
                                    <div class="flex shrink-0 gap-3 text-xs font-medium">
                                        <button type="button" @click="edit('program', i)" class="text-brand hover:underline">Edit</button>
                                        <button type="button" @click="remove(i)" class="text-gray-400 hover:text-red-600">Remove</button>
                                    </div>
                                </div>
                                <ul class="mt-1 space-y-0.5 text-xs text-gray-600">
                                    <template x-for="(meeting, j) in program.meetings" :key="j">
                                        <li x-text="'Meeting ' + (j + 1) + ': ' + meetingLabel(meeting)"></li>
                                    </template>
                                </ul>
                                <div x-show="priceOf(program) > 0" x-cloak class="mt-2 flex justify-between border-t border-gray-100 pt-2 text-xs">
                                    <span class="text-gray-500" x-text="program.payment_plan === 'installment' && installmentAllowed(program) ? 'Program fee · 50% now, 50% later' : 'Program fee · paid in full'"></span>
                                    <span class="font-semibold text-gray-800" x-text="rupiah(priceOf(program))"></span>
                                </div>
                            </div>
                        </template>

                        <div x-show="programs.length && (totalFee() > 0 || registrationFee() > 0)" x-cloak class="rounded-md border border-brand/30 bg-white px-3 py-3 text-sm">
                            <div class="flex justify-between"><span class="text-gray-600">Total program fee</span><span class="font-semibold" x-text="rupiah(totalFee())"></span></div>
                            <div x-show="registrationFee() > 0" class="flex justify-between"><span class="text-gray-600">Registration fee <span class="text-xs text-gray-400">(one-time, non-refundable)</span></span><span class="font-semibold" x-text="rupiah(registrationFee())"></span></div>
                            <p x-show="registrationFee() > 0" class="mt-1 text-xs text-gray-500">Your program starts once the registration fee is paid.</p>
                            <div class="mt-2 flex justify-between border-t border-gray-100 pt-2"><span class="font-medium text-gray-700">Total to pay when you register</span><span class="text-base font-bold text-brand" x-text="rupiah(dueNow() + registrationFee())"></span></div>
                            <p class="mt-1 text-xs text-gray-400">These are standard prices. Our team confirms the final fee and payment details when approving your registration.</p>
                        </div>

                        <div x-show="programs.length" x-cloak class="rounded-md border border-gray-200 bg-gray-50 p-3">
                            <label class="flex items-start gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="terms_accepted" value="1" x-ref="terms" required class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                                <span>I have read and agree to the terms &amp; conditions of the program(s) selected above<span x-show="anyTermsToAgree()"> and shown next to each one</span>, as the agreement between myself and PT Alfajar Logic Futura.</span>
                            </label>
                            @if ($hasAgreement)
                                <p class="mt-2 pl-6 text-sm"><a href="{{ route('customer-registration.agreement') }}" target="_blank" rel="noopener noreferrer" class="font-medium text-brand underline hover:text-brand-dark">Read the full agreement</a></p>
                            @endif
                            @error('terms_accepted') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <p x-show="attempted && !allReady()" x-cloak class="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">Please choose a date and time on the calendar for every meeting.</p>

                        <div class="flex gap-2">
                            <button type="button" @click="back()" class="rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Back</button>
                            <button type="submit" class="flex-1 rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-brand-dark hover:to-brand-dark hover:shadow-md transition-all duration-150">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </body>
</html>
