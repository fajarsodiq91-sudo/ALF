<x-layouts.erp title="Edit Training Session">
    @include('erp.partials.slot-picker', ['booked' => $booked])
    @php $inputClass = 'block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm'; @endphp

    <div class="max-w-3xl space-y-6" x-data="{ openSection: @js(request()->query('focus')) }">
        <x-erp.flash />

        <div class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow duration-200 hover:shadow-lg" x-data="{ key: 'details' }">
            <button type="button" @click="openSection = (openSection === key ? null : key)" class="flex w-full items-center justify-between px-6 py-4 text-left">
                <span class="text-sm font-semibold text-gray-800">Session details</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-gray-400 transition-transform" :class="{ 'rotate-180': openSection === key }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
            </button>
            <div x-show="openSection === key" x-cloak class="px-6 pb-6">
                <form action="{{ route('training.update', $session) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('erp.training.sessions._form')
                </form>
            </div>
        </div>

        @if ($session->acceptsParticipants())
            <div class="bg-white rounded-lg shadow-md border border-gray-200" x-data="{ key: 'participants' }">
                <button type="button" @click="openSection = (openSection === key ? null : key)" class="flex w-full items-center justify-between px-4 py-3 text-left">
                    <span class="text-sm font-semibold text-gray-800">Participants</span>
                    <span class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">{{ $session->participants->count() }} of {{ $session->participant_limit }} joined</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-gray-400 transition-transform" :class="{ 'rotate-180': openSection === key }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                    </span>
                </button>
            <div x-show="openSection === key" x-cloak class="px-4 pb-4 space-y-3">
                @if ($session->participantLinkOpen())
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Participant link (the company also sees it in its portal)</label>
                        <input type="text" readonly value="{{ route('participant.show', $session->participant_token) }}" onclick="this.select()" class="mt-1 {{ $inputClass }}">
                    </div>
                @else
                    <p class="rounded-md bg-gray-50 px-3 py-2 text-xs text-gray-500">The link is closed: the session is full, completed or cancelled.</p>
                @endif
                @if ($session->participants->isNotEmpty())
                    <ul class="divide-y divide-gray-100 rounded-md border border-gray-200 text-sm">
                        @foreach ($session->participants as $participant)
                            <li class="flex flex-wrap items-center justify-between gap-2 px-3 py-2">
                                <span><span class="font-medium text-gray-800">{{ $participant->name }}</span> <span class="text-xs text-gray-400">{{ $participant->email }} · {{ $participant->customer_code }}</span></span>
                                @can('training.manage')
                                    <form action="{{ route('training.participants.destroy', [$session, $participant]) }}" method="POST" onsubmit="return confirm('Remove this participant from the session?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">Remove</button>
                                    </form>
                                @endcan
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-x-auto" x-data="{ key: 'payments' }">
            <button type="button" @click="openSection = (openSection === key ? null : key)" class="flex w-full items-center justify-between px-4 py-3 border-b border-gray-200 text-left">
                <span class="text-sm font-semibold text-gray-800">Payments</span>
                <span class="flex items-center gap-2">
                    <span class="text-xs text-gray-500">
                        {{ \App\Services\SessionPaymentPlan::rupiah($session->paidAmount()) }} received of {{ \App\Services\SessionPaymentPlan::rupiah($session->fee) }}
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-gray-400 transition-transform" :class="{ 'rotate-180': openSection === key }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                </span>
            </button>
            <div x-show="openSection === key" x-cloak>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Payment</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Amount</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Due</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        @can('finance.manage')
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                        @endcan
                    </tr>
                </thead>
                @forelse ($session->payments as $payment)
                    <tbody x-data="{ paying: false, method: 'Bank Transfer' }" class="divide-y divide-gray-100 border-t border-gray-100">
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $payment->label }}</td>
                            <td class="px-4 py-3 text-right text-gray-800">{{ \App\Services\SessionPaymentPlan::rupiah($payment->amount) }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $payment->dueLabel() }}</td>
                            <td class="px-4 py-3">
                                @if ($payment->isPaid())
                                    <span class="inline-flex rounded-full bg-green-50 text-green-700 px-2 py-0.5 text-xs font-medium">Paid {{ $payment->paid_date?->format('d M Y') }}</span>
                                @elseif ($payment->isDue())
                                    <span class="inline-flex rounded-full bg-amber-50 text-amber-700 px-2 py-0.5 text-xs font-medium">Due now</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 text-gray-500 px-2 py-0.5 text-xs font-medium">Not yet due</span>
                                @endif
                            </td>
                            @can('finance.manage')
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    @if ($payment->isPaid())
                                        <div class="inline-flex items-center gap-3">
                                            @if ($payment->hasProof())
                                                @if ($payment->proof_path)
                                                    <a href="{{ route('training.payments.proof', $payment) }}" class="text-brand hover:text-brand-dark font-medium">View proof</a>
                                                @else
                                                    <a href="{{ $payment->proof_url }}" target="_blank" rel="noopener" class="text-brand hover:text-brand-dark font-medium">View proof</a>
                                                @endif
                                            @endif
                                            <form action="{{ route('training.payments.cancel', $payment) }}" method="POST" class="inline" onsubmit="return confirm('Cancel this payment? The Finance income will be removed.');">
                                                @csrf
                                                <button type="submit" class="text-amber-600 hover:text-amber-800 font-medium">Cancel payment</button>
                                            </form>
                                        </div>
                                    @else
                                        <button type="button" @click="paying = !paying" class="text-green-600 hover:text-green-800 font-medium">Record payment</button>
                                    @endif
                                </td>
                            @endcan
                        </tr>
                        @if (! $payment->isPaid())
                            @can('finance.manage')
                                <tr x-show="paying" x-cloak class="bg-gray-50">
                                    <td colspan="5" class="px-4 py-3">
                                        <form action="{{ route('training.payments.pay', $payment) }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
                                            @csrf
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600">Method</label>
                                                <select name="payment_method" x-model="method" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                                    @foreach (\App\Models\Payroll::PAYMENT_METHODS as $paymentMethod)
                                                        <option value="{{ $paymentMethod }}">{{ $paymentMethod }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div x-show="method === 'Bank Transfer'">
                                                <label class="block text-xs font-medium text-gray-600">Received in account</label>
                                                <select name="account_id" :required="method === 'Bank Transfer'" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                                    @foreach ($accounts as $account)
                                                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600">Date received</label>
                                                <input type="date" name="paid_date" value="{{ now()->format('Y-m-d') }}" required class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                            </div>
                                            <div x-show="method === 'Bank Transfer'">
                                                <label class="block text-xs font-medium text-gray-600">Proof of transfer (file)</label>
                                                <input type="file" name="proof" accept=".pdf,.png,.jpg,.jpeg,.webp" class="mt-1 block text-xs text-gray-600 file:mr-2 file:rounded-md file:border-0 file:bg-gray-100 file:px-2 file:py-1.5 file:text-xs file:font-medium file:text-gray-700 hover:file:bg-gray-200">
                                            </div>
                                            <div x-show="method === 'Bank Transfer'">
                                                <label class="block text-xs font-medium text-gray-600">...or a link to the proof</label>
                                                <input type="url" name="proof_url" placeholder="https://" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                            </div>
                                            <button type="submit" class="rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white shadow-sm hover:from-brand-dark hover:to-brand-dark">Confirm &amp; add to Finance income</button>
                                        </form>
                                    </td>
                                </tr>
                            @endcan
                        @endif
                    </tbody>
                @empty
                    <tbody><tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No payments. Set a fee above to create the payment schedule.</td></tr></tbody>
                @endforelse
            </table>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-x-auto" x-data="{ key: 'meetings' }">
            <button type="button" @click="openSection = (openSection === key ? null : key)" class="flex w-full items-center justify-between px-4 py-3 border-b border-gray-200 text-left">
                <span class="text-sm font-semibold text-gray-800">Meeting schedule</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-gray-400 transition-transform" :class="{ 'rotate-180': openSection === key }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
            </button>
            <div x-show="openSection === key" x-cloak>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Date</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Time</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Place</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Topic</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Realised</th>
                        @can('training.manage')
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($session->meetings as $meeting)
                        <tr>
                            <td class="px-4 py-3 text-gray-800">{{ $meeting->meeting_date->format('D, d M Y') }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $meeting->timeRange() ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $meeting->location ?: ($session->location ?: '—') }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $meeting->topic ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($meeting->is_completed)
                                    <span class="inline-flex rounded-full bg-green-50 text-green-700 px-2 py-0.5 text-xs font-medium">Done {{ $meeting->completed_at?->format('d M') }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 text-gray-500 px-2 py-0.5 text-xs font-medium">Upcoming</span>
                                @endif
                            </td>
                            @can('training.manage')
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <form action="{{ route('training.meetings.toggle', $meeting) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-brand hover:text-brand-dark font-medium">{{ $meeting->is_completed ? 'Undo' : 'Mark done' }}</button>
                                    </form>
                                    <form action="{{ route('training.meetings.destroy', $meeting) }}" method="POST" class="inline" onsubmit="return confirm('Delete this meeting?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-gray-400 hover:text-red-600 font-medium">Delete</button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                        @php $pendingReschedule = $meeting->rescheduleRequests->firstWhere('status', 'pending'); @endphp
                        @if ($pendingReschedule)
                            <tr class="bg-amber-50">
                                <td colspan="6" class="px-4 py-3 text-sm">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <span class="font-medium text-amber-800">Customer requested a reschedule</span>
                                            <span class="text-amber-700">to {{ $pendingReschedule->requestedLabel() }}</span>
                                            @if ($pendingReschedule->reason)
                                                <span class="mt-0.5 block text-xs italic text-amber-600">&ldquo;{{ $pendingReschedule->reason }}&rdquo;</span>
                                            @endif
                                        </div>
                                        @can('training.manage')
                                            <div class="flex shrink-0 gap-2">
                                                <form action="{{ route('training.reschedule-requests.approve', $pendingReschedule) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="rounded-md bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">Approve</button>
                                                </form>
                                                <form action="{{ route('training.reschedule-requests.reject', $pendingReschedule) }}" method="POST" onsubmit="return confirm('Reject this reschedule request?');">
                                                    @csrf
                                                    <button type="submit" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Reject</button>
                                                </form>
                                            </div>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">No meetings scheduled yet.</td></tr>
                    @endforelse
                </tbody>
            </table>

            @can('training.manage')
                <form action="{{ route('training.meetings.store', $session) }}" method="POST"
                      x-data="{ m: { meeting_date: @js(old('meeting_date', '')), start_time: @js(old('start_time', '')), end_time: @js(old('end_time', '')) }, hours: window.operatingHours }" @submit="if (!meetingReady(m)) { $event.preventDefault(); alert('Choose a date and time on the calendar first.'); }"
                      class="grid grid-cols-2 sm:grid-cols-6 gap-2 items-start border-t border-gray-200 bg-gray-50 p-4">
                    @csrf
                    <div class="col-span-2 sm:col-span-6 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-sm" :class="m.meeting_date ? 'font-medium text-gray-800' : 'text-gray-400'" x-text="meetingLabel(m)"></p>
                            @error('meeting_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            @error('end_time') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button type="button" @click="$dispatch('open-slot-picker', { meeting: m, siblings: [], minutes: @js($session->program?->session_minutes) })" class="shrink-0 inline-flex items-center gap-1.5 rounded-md border border-brand/40 bg-white px-3 py-1.5 text-sm font-medium text-brand shadow-sm transition hover:bg-brand-50 hover:shadow"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg> Choose on calendar</button>
                    </div>
                    <template x-if="hours.enforced">
                        <div class="hidden">
                            <input type="hidden" name="meeting_date" :value="m.meeting_date">
                            <input type="hidden" name="start_time" :value="m.start_time">
                            <input type="hidden" name="end_time" :value="m.end_time">
                        </div>
                    </template>
                    <template x-if="!hours.enforced">
                        <div class="col-span-2 sm:col-span-6 grid grid-cols-3 gap-2">
                            <input type="date" name="meeting_date" x-model="m.meeting_date" required class="{{ $inputClass }}">
                            <input type="time" name="start_time" x-model="m.start_time" class="{{ $inputClass }}">
                            <input type="time" name="end_time" x-model="m.end_time" class="{{ $inputClass }}">
                        </div>
                    </template>
                    <input type="text" name="location" value="{{ old('location') }}" placeholder="Place" class="col-span-2 sm:col-span-3 {{ $inputClass }}">
                    <input type="text" name="topic" value="{{ old('topic') }}" placeholder="Topic" class="col-span-2 sm:col-span-3 {{ $inputClass }}">
                    <div class="col-span-2 sm:col-span-6">
                        <button type="submit" class="rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white shadow-sm hover:from-brand-dark hover:to-brand-dark">Add Meeting</button>
                    </div>
                </form>
            @endcan
            </div>
        </div>
    </div>
</x-layouts.erp>
