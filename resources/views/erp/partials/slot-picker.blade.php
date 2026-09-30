{{--
    Calendar pop-up for choosing a meeting slot. Green = available, red = booked by someone else.
    Open it with: $dispatch('open-slot-picker', { meeting, siblings, minutes, corporate })  where `meeting` is the
    reactive { meeting_date, start_time, end_time } object to fill, `siblings` are the other meetings of the same
    form, and `corporate` (bool) opens the corporate-only slots — pass the program's/session's is_corporate flag.
    Expects $booked: the taken slot keys from App\Services\BookedSlots::keys().
--}}
@include('erp.partials.operating-hours')
<script>
    window.bookedSlots = @json($booked);
    window.windowMonths = 6;

    window.pad2 = n => String(n).padStart(2, '0');
    window.isoDate = d => d.getFullYear() + '-' + window.pad2(d.getMonth() + 1) + '-' + window.pad2(d.getDate());

    /** "Tue, 06 Oct 2026 · 20:00 – 21:30" for a meeting object, or a prompt when nothing is chosen yet. */
    window.meetingLabel = function (meeting) {
        if (!meeting.meeting_date) { return 'No date and time chosen yet'; }
        const d = new Date(meeting.meeting_date + 'T00:00:00');
        const day = d.toLocaleDateString('en-GB', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });
        return meeting.start_time ? day + ' · ' + meeting.start_time + ' – ' + meeting.end_time : day + ' · choose a time slot';
    };

    /** True when the meeting has the date (and, on a strict schedule, the slot) filled in. */
    window.meetingReady = meeting => !!meeting.meeting_date && (!window.operatingHours.enforced || (!!meeting.start_time && !!meeting.end_time));

    function slotPicker() {
        return {
            open: false, meeting: null, siblings: [], cursor: new Date(), today: window.isoDate(new Date()), minutes: null, focus: null, corporate: false,

            show(detail) {
                this.meeting = detail.meeting;
                this.siblings = detail.siblings || [];
                this.minutes = detail.minutes ? parseInt(detail.minutes) : null;
                this.corporate = !!detail.corporate;
                this.focus = detail.meeting.meeting_date || null;
                const start = detail.meeting.meeting_date ? new Date(detail.meeting.meeting_date + 'T00:00:00') : new Date();
                this.cursor = new Date(start.getFullYear(), start.getMonth(), 1);
                this.open = true;
            },
            close() { this.open = false; },

            firstMonth() { const n = new Date(); return new Date(n.getFullYear(), n.getMonth(), 1); },
            lastMonth() { const n = new Date(); return new Date(n.getFullYear(), n.getMonth() + window.windowMonths, 1); },
            canPrev() { return this.cursor > this.firstMonth(); },
            canNext() { return this.cursor < this.lastMonth(); },
            move(delta) { this.cursor = new Date(this.cursor.getFullYear(), this.cursor.getMonth() + delta, 1); },
            monthLabel() { return this.cursor.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' }); },

            /** 'available' | 'booked' | 'yours' (this meeting) | 'picked' (another meeting of the same form). */
            stateOf(date, slot) {
                if (this.meeting && this.meeting.meeting_date === date && this.meeting.start_time === slot.start && this.meeting.end_time === slot.end) { return 'yours'; }
                if (this.siblings.some(m => m !== this.meeting && m.meeting_date === date && m.start_time && window.toMin(m.start_time) < window.toMin(slot.end) && window.toMin(m.end_time) > window.toMin(slot.start))) { return 'picked'; }
                return window.overlapsBooked(window.bookedSlots, date, slot.start, slot.end) ? 'booked' : 'available';
            },

            /** Six weeks starting on Monday, each day with its slots and their state. */
            weeks() {
                const first = new Date(this.cursor);
                const offset = (first.getDay() + 6) % 7;
                const start = new Date(first.getFullYear(), first.getMonth(), 1 - offset);
                const weeks = [];
                for (let w = 0; w < 6; w++) {
                    const days = [];
                    for (let d = 0; d < 7; d++) {
                        const day = new Date(start.getFullYear(), start.getMonth(), start.getDate() + w * 7 + d);
                        const iso = window.isoDate(day);
                        days.push({
                            iso, number: day.getDate(), inMonth: day.getMonth() === this.cursor.getMonth(), past: iso < this.today,
                            open: window.slotsFor(iso, this.corporate).length > 0, slots: window.candidateSlots(iso, this.minutes, this.corporate).map(slot => ({ ...slot, state: this.stateOf(iso, slot) })),
                        });
                    }
                    weeks.push(days);
                }
                return weeks;
            },

            /** Free start times of a day (only used when the program has a session length). */
            free(day) { return day.past ? 0 : day.slots.filter(s => s.state === 'available' || s.state === 'yours').length; },
            focused() { return this.weeks().flat().find(d => d.iso === this.focus) || null; },

            choose(day, slot) {
                if (day.past || slot.state === 'booked' || slot.state === 'picked') { return; }
                this.meeting.meeting_date = day.iso;
                this.meeting.start_time = slot.start;
                this.meeting.end_time = slot.end;
                this.close();
            },
        };
    }
</script>

<div x-data="slotPicker()" @open-slot-picker.window="show($event.detail)" @keydown.escape.window="close()"
     @click.self="close()"
     x-show="open" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:items-center" role="dialog" aria-modal="true" aria-label="Choose a date and time">
    <div class="w-full max-w-4xl rounded-xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
            <div class="flex items-center gap-2">
                <button type="button" @click="move(-1)" :disabled="!canPrev()" class="rounded-md border border-gray-300 px-2.5 py-1 text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-30" aria-label="Previous month">&larr;</button>
                <h3 class="min-w-[9rem] text-center text-base font-semibold text-gray-800" x-text="monthLabel()"></h3>
                <button type="button" @click="move(1)" :disabled="!canNext()" class="rounded-md border border-gray-300 px-2.5 py-1 text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-30" aria-label="Next month">&rarr;</button>
            </div>
            <button type="button" @click="close()" class="rounded-md px-2 py-1 text-sm text-gray-500 hover:bg-gray-100">Close</button>
        </div>

        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 border-b border-gray-100 px-4 py-2 text-xs text-gray-600">
            <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-green-100 ring-1 ring-green-400"></span> Available</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-red-100 ring-1 ring-red-400"></span> Booked</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-brand ring-1 ring-brand-dark"></span> Your choice</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-amber-100 ring-1 ring-amber-400"></span> Already used by another meeting here</span>
            <span class="text-gray-400">Click a green slot to choose it.</span>
        </div>

        <div class="overflow-x-auto p-3">
            <div class="min-w-[44rem]">
                <div class="grid grid-cols-7 gap-1 pb-1 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <template x-for="name in ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']" :key="name"><div x-text="name"></div></template>
                </div>
                <template x-for="(week, w) in weeks()" :key="w">
                    <div class="mb-1 grid grid-cols-7 gap-1">
                        <template x-for="day in week" :key="day.iso">
                            <div class="min-h-[5.5rem] rounded-md border p-1"
                                 :class="[day.inMonth ? 'border-gray-200 bg-white' : 'border-gray-100 bg-gray-50', day.past ? 'opacity-50' : '']">
                                <div class="flex items-center justify-between px-0.5 text-xs">
                                    <span :class="day.iso === today ? 'rounded-full bg-brand px-1.5 font-semibold text-white' : (day.inMonth ? 'font-medium text-gray-700' : 'text-gray-400')" x-text="day.number"></span>
                                    <span x-show="!day.open && day.inMonth && !day.past" class="text-[10px] uppercase text-gray-300">Closed</span>
                                    <span x-show="day.open && !day.slots.length && day.inMonth && !day.past" class="text-[10px] uppercase text-amber-500" title="The operating hours of this day are shorter than the program's session">Too short</span>
                                </div>
                                <div class="mt-1" x-show="minutes && day.slots.length">
                                    <button type="button" @click="focus = day.iso" :disabled="day.past"
                                            class="block w-full rounded px-1 py-0.5 text-left text-[11px] leading-tight ring-1"
                                            :class="[free(day) ? 'bg-green-100 text-green-800 ring-green-300 hover:bg-green-200' : 'bg-red-100 text-red-700 ring-red-300', focus === day.iso ? 'outline outline-2 outline-brand' : '']"
                                            x-text="free(day) ? free(day) + ' times free' : 'Full'"></button>
                                </div>
                                <div class="mt-1 space-y-0.5" x-show="!minutes">
                                    <template x-for="slot in day.slots" :key="slot.value">
                                        <button type="button" @click="choose(day, slot)"
                                                :disabled="day.past || slot.state === 'booked' || slot.state === 'picked'"
                                                :title="slot.state === 'booked' ? 'Already booked' : (slot.state === 'available' ? 'Available: click to choose' : (slot.state === 'yours' ? 'Your current choice' : 'Used by another meeting in this form'))"
                                                class="block w-full rounded px-1 py-0.5 text-left text-[11px] leading-tight ring-1 transition"
                                                :class="{
                                                    'bg-green-100 text-green-800 ring-green-300 hover:bg-green-200 hover:ring-green-500': slot.state === 'available' && !day.past,
                                                    'bg-red-100 text-red-700 ring-red-300 line-through cursor-not-allowed': slot.state === 'booked',
                                                    'bg-brand text-white ring-brand-dark': slot.state === 'yours',
                                                    'bg-amber-100 text-amber-800 ring-amber-300 cursor-not-allowed': slot.state === 'picked',
                                                    'bg-gray-100 text-gray-400 ring-gray-200': day.past && slot.state === 'available',
                                                }"
                                                x-text="slot.label"></button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="minutes" x-cloak class="border-t border-gray-100 px-4 py-3">
            <template x-if="focused()">
                <div>
                    <p class="mb-2 text-sm font-medium text-gray-700"><span x-text="focused().iso"></span> · start times for a <span x-text="minutes"></span>-minute session</p>
                    <p x-show="!focused().slots.length" class="text-sm text-gray-400" x-text="focused().open ? 'The operating hours of this day are shorter than the session length. Extend them in Master Data → Operating Hours.' : 'Closed on this day.'"></p>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="slot in focused().slots" :key="slot.value">
                            <button type="button" @click="choose(focused(), slot)" :disabled="focused().past || slot.state === 'booked' || slot.state === 'picked'"
                                    class="rounded px-2 py-1 text-xs ring-1"
                                    :class="{
                                        'bg-green-100 text-green-800 ring-green-300 hover:bg-green-200': slot.state === 'available',
                                        'bg-red-100 text-red-700 ring-red-300 line-through cursor-not-allowed': slot.state === 'booked',
                                        'bg-brand text-white ring-brand-dark': slot.state === 'yours',
                                        'bg-amber-100 text-amber-800 ring-amber-300 cursor-not-allowed': slot.state === 'picked',
                                    }" x-text="slot.label"></button>
                        </template>
                    </div>
                </div>
            </template>
            <p x-show="!focus" class="text-sm text-gray-400">Click a day to see its available start times.</p>
        </div>
    </div>
</div>
