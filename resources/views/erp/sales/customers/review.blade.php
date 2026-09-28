<x-layouts.erp title="Review Registration">
    @php
        $emptyProgram = ['program_type' => '', 'payment_plan' => 'full', 'instructor_id' => '', 'training_program_id' => '', 'delivery_mode' => 'onsite', 'location' => '', 'fee' => '', 'meetings' => [['meeting_date' => '', 'start_time' => '', 'end_time' => '', 'location' => '', 'topic' => '']]];
        $emptyMeeting = ['meeting_date' => '', 'start_time' => '', 'end_time' => '', 'location' => '', 'topic' => ''];
        $modes = \App\Services\MasterData::options('delivery_mode');
        $prefill = collect($requested)->map(fn ($entry) => [
            'program_type' => $entry['program']->program_type,
            'training_program_id' => (string) $entry['program']->id,
            'instructor_id' => '',
            'payment_plan' => $entry['plan'],
            'delivery_mode' => array_key_first($modes) ?? 'onsite',
            'location' => '',
            'fee' => (string) (float) $entry['program']->standard_price,
            'meetings' => collect($entry['meetings'])->map(fn ($meeting) => [
                'meeting_date' => $meeting['date']->format('Y-m-d'),
                'start_time' => $meeting['start'] ? substr($meeting['start'], 0, 5) : '',
                'end_time' => $meeting['end'] ? substr($meeting['end'], 0, 5) : '',
                'location' => '',
                'topic' => '',
            ])->all(),
        ])->all();
        $catalog = $programs->map(fn ($program) => ['id' => $program->id, 'name' => $program->name, 'type' => $program->program_type])->values();
        $inputClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm';
    @endphp

    @include('erp.partials.operating-hours')
    <script>
        function reviewForm(programs, emptyProgram, emptyMeeting, catalog) {
            return {
                programs, emptyProgram, emptyMeeting, catalog, hours: window.operatingHours,
                rupiah(amount) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount); },
                /** Mirrors the server's payment plan so the split is visible before approving. */
                paymentPreview(program) {
                    const fee = parseFloat(program.fee) || 0;
                    if (fee <= 0) { return []; }
                    if (program.payment_plan === 'installment') {
                        const first = Math.round(fee / 2 * 100) / 100;
                        const middle = Math.max(1, Math.ceil(program.meetings.length / 2));
                        return [
                            { label: 'Down payment (50%)', amount: first, when: 'Upon registration' },
                            { label: 'Final payment (50%)', amount: Math.round((fee - first) * 100) / 100, when: 'At meeting ' + middle },
                        ];
                    }
                    return [{ label: 'Full payment', amount: fee, when: 'Upon registration' }];
                },
            };
        }
    </script>

    <div class="max-w-4xl space-y-6" x-data='reviewForm(@json(old("programs", $prefill ?: [$emptyProgram])), @json($emptyProgram), @json($emptyMeeting), @json($catalog))'>
        <x-erp.flash />

        @if ($errors->any())
            <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                <p class="font-medium">Please fix the following:</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach (collect($errors->all())->unique() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
            <div class="flex items-start gap-4">
                @if ($customer->photoUrl())
                    <img src="{{ $customer->photoUrl() }}" alt="" class="h-16 w-16 rounded-full object-cover ring-1 ring-gray-200">
                @endif
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">{{ $customer->name }}</h2>
                    <p class="text-sm text-gray-500">{{ \App\Services\MasterData::label('customer_type', $customer->customer_type) }} · submitted {{ $customer->submitted_at?->format('d M Y, H:i') }}</p>
                </div>
            </div>
            <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div><dt class="text-gray-500">Email</dt><dd class="text-gray-800">{{ $customer->email }}</dd></div>
                <div><dt class="text-gray-500">Phone</dt><dd class="text-gray-800">{{ $customer->phone ?: '—' }}</dd></div>
                <div><dt class="text-gray-500">City</dt><dd class="text-gray-800">{{ $customer->city ?: '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-gray-500">Address</dt><dd class="text-gray-800">{{ $customer->address ?: '—' }}</dd></div>
            </dl>
            <p class="mt-3 text-xs text-gray-500">Need to correct something first? <a href="{{ route('sales.edit', $customer) }}" class="text-brand hover:text-brand-dark font-medium">Edit the details</a>.</p>
        </div>

        @if ($requested)
            <div class="rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                <p class="font-medium">Requested by the customer</p>
                <p class="text-xs text-blue-700">These are pre-filled below. Adjust the dates, mode, place, and fee, or remove anything you cannot offer.</p>
                <ul class="mt-2 space-y-1">
                    @foreach ($requested as $entry)
                        <li><span class="font-medium">{{ $entry['program']->name }}</span> ({{ \App\Services\SessionPaymentPlan::rupiah($entry['price']) }}, {{ \App\Services\SessionPaymentPlan::PLANS[$entry['plan']] ?? $entry['plan'] }}): {{ collect($entry['meetings'])->pluck('label')->implode(' · ') }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('sales.approve', $customer) }}" method="POST" class="space-y-6">
            @csrf

            @if ($programs->isEmpty())
                <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                    <p class="font-medium">There are no active programs to choose from yet.</p>
                    <p class="mt-1">
                        The program list comes from your catalog. Add at least one program (choose its type, Learning or Consulting) first, then come back to this page.
                        @can('training.manage')
                            <a href="{{ route('training.programs.create') }}" class="font-medium underline">Add a program</a>.
                        @endcan
                    </p>
                </div>
            @endif

            <template x-for="(program, i) in programs" :key="i">
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-800" x-text="'Program ' + (i + 1)"></h3>
                        <button type="button" x-show="programs.length > 1" @click="programs.splice(i, 1)" class="text-sm text-gray-400 hover:text-red-600">Remove program</button>
                    </div>

                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Program type</label>
                            <select x-model="program.program_type" @change="program.training_program_id = ''" class="{{ $inputClass }}">
                                <option value="">All types</option>
                                @foreach ($programTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Program</label>
                            <select :name="`programs[${i}][training_program_id]`" x-model="program.training_program_id" required class="{{ $inputClass }}">
                                <option value="">Select program</option>
                                <template x-for="item in catalog.filter(c => !program.program_type || c.type === program.program_type)" :key="item.id">
                                    <option :value="item.id" :selected="String(item.id) === String(program.training_program_id)" x-text="item.name"></option>
                                </template>
                            </select>
                            <p x-show="catalog.filter(c => !program.program_type || c.type === program.program_type).length === 0" x-cloak class="mt-1 text-xs text-amber-700">
                                No active program of this type yet.
                                @can('training.manage')
                                    <a href="{{ route('training.programs.create') }}" class="font-medium underline">Add one in Training &gt; Programs</a>.
                                @endcan
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Delivery mode</label>
                            <select :name="`programs[${i}][delivery_mode]`" x-model="program.delivery_mode" required class="{{ $inputClass }}">
                                @foreach ($modes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Instructor (optional)</label>
                            <select :name="`programs[${i}][instructor_id]`" x-model="program.instructor_id" class="{{ $inputClass }}">
                                <option value="">Not assigned yet</option>
                                @foreach ($instructors as $instructor)
                                    <option value="{{ $instructor->id }}">{{ $instructor->name }}@if ($instructor->position) ({{ $instructor->position }})@endif</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fee (Rp)</label>
                            <input type="number" min="0" step="0.01" :name="`programs[${i}][fee]`" x-model="program.fee" class="{{ $inputClass }}">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Payment plan</label>
                            <select :name="`programs[${i}][payment_plan]`" x-model="program.payment_plan" class="{{ $inputClass }}">
                                @foreach (\App\Services\SessionPaymentPlan::PLANS as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <ul x-show="paymentPreview(program).length" x-cloak class="mt-2 rounded-md bg-brand-50 px-3 py-2 text-xs text-gray-600">
                                <template x-for="payment in paymentPreview(program)" :key="payment.label">
                                    <li><span class="font-medium text-gray-700" x-text="payment.label"></span>: <span x-text="rupiah(payment.amount)"></span> &mdash; <span x-text="payment.when"></span></li>
                                </template>
                            </ul>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Default location</label>
                            <input type="text" :name="`programs[${i}][location]`" x-model="program.location" placeholder="Used for meetings without their own location" class="{{ $inputClass }}">
                        </div>
                    </div>

                    <h4 class="mt-5 text-sm font-medium text-gray-700">Meeting schedule</h4>
                    <div class="mt-2 space-y-3">
                        <template x-for="(meeting, j) in program.meetings" :key="j">
                            <div class="grid grid-cols-2 sm:grid-cols-6 gap-2 items-end rounded-md bg-gray-50 p-3">
                                <div class="col-span-2">
                                    <label class="block text-xs text-gray-500">Date</label>
                                    <input type="date" required :name="`programs[${i}][meetings][${j}][meeting_date]`" x-model="meeting.meeting_date" @change="syncSlot(meeting)" class="{{ $inputClass }}">
                                    <p x-show="hoursHint(meeting.meeting_date)" x-text="hoursHint(meeting.meeting_date)" x-cloak class="mt-1 text-xs text-red-600"></p>
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs text-gray-500">Time slot</label>
                                    <select @change="pickSlot(meeting, $event.target.value)" :required="hours.enforced" class="{{ $inputClass }}">
                                        <option value="">Select slot</option>
                                        <template x-for="slot in slotsFor(meeting.meeting_date)" :key="slot.value">
                                            <option :value="slot.value" :selected="slot.start === meeting.start_time && slot.end === meeting.end_time" x-text="slot.label"></option>
                                        </template>
                                    </select>
                                </div>
                                <div x-show="!hours.enforced">
                                    <label class="block text-xs text-gray-500">Start</label>
                                    <input type="time" :name="`programs[${i}][meetings][${j}][start_time]`" x-model="meeting.start_time" class="{{ $inputClass }}">
                                </div>
                                <div x-show="!hours.enforced">
                                    <label class="block text-xs text-gray-500">End</label>
                                    <input type="time" :name="`programs[${i}][meetings][${j}][end_time]`" x-model="meeting.end_time" class="{{ $inputClass }}">
                                </div>
                                <template x-if="hours.enforced">
                                    <div class="hidden">
                                        <input type="hidden" :name="`programs[${i}][meetings][${j}][start_time]`" :value="meeting.start_time">
                                        <input type="hidden" :name="`programs[${i}][meetings][${j}][end_time]`" :value="meeting.end_time">
                                    </div>
                                </template>
                                <div class="col-span-2 sm:col-span-3">
                                    <label class="block text-xs text-gray-500">Place (optional)</label>
                                    <input type="text" :name="`programs[${i}][meetings][${j}][location]`" x-model="meeting.location" class="{{ $inputClass }}">
                                </div>
                                <div class="col-span-2 sm:col-span-2">
                                    <label class="block text-xs text-gray-500">Topic (optional)</label>
                                    <input type="text" :name="`programs[${i}][meetings][${j}][topic]`" x-model="meeting.topic" class="{{ $inputClass }}">
                                </div>
                                <div class="text-right">
                                    <button type="button" x-show="program.meetings.length > 1" @click="program.meetings.splice(j, 1)" class="text-sm text-gray-400 hover:text-red-600">Remove</button>
                                </div>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="program.meetings.push({ ...emptyMeeting })" class="mt-3 text-sm font-medium text-brand hover:text-brand-dark">+ Add meeting</button>
                </div>
            </template>

            <button type="button" @click="programs.push(JSON.parse(JSON.stringify(emptyProgram)))" class="text-sm font-medium text-brand hover:text-brand-dark">+ Add another program</button>

            <div class="flex items-center gap-3">
                <button type="submit" @disabled($programs->isEmpty()) class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150 disabled:cursor-not-allowed disabled:opacity-50">Approve &amp; Send Email</button>
                <a href="{{ route('sales.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
            <p class="text-xs text-gray-500">
                Approving assigns the permanent customer ID and emails the customer their details, the schedule above, and the portal login link
                (username and initial password are both the customer ID; they must choose a new password at first login).
            </p>
        </form>

        <form action="{{ route('sales.reject', $customer) }}" method="POST" class="bg-white rounded-lg shadow-md border border-gray-200 p-6" onsubmit="return confirm('Reject this registration?');">
            @csrf
            <h3 class="text-sm font-semibold text-gray-800">Reject instead</h3>
            <p class="text-xs text-gray-500 mb-2">The customer will be told by email that the registration was not approved.</p>
            <textarea name="rejection_reason" rows="2" placeholder="Reason (optional). It is included in the email sent to the customer." class="{{ $inputClass }}"></textarea>
            <button type="submit" class="mt-3 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-red-600 shadow-sm hover:bg-red-50">Reject Registration</button>
        </form>
    </div>
</x-layouts.erp>
