{{--
    Month calendar of the company's operating hours: green = available, red = booked (by a customer or blocked by the company).
    Read-only by default. With $manage the company can click a green slot to block it, or a grey (blocked) slot to free it.
    Optional: $blocks (key => id, from BlockedSlot), $blockUrl (POST), $unblockUrl (DELETE, with "__ID__" as the placeholder).
    Optional: $details (key => list of booking detail arrays, from BookedSlots::details()) shows a tooltip on hover and
    a read-only popup on click for booked slots; omit it to keep booked slots inert, as on the customer portal.
--}}
@php
    $manage = $manage ?? false;
    $blocks = $blocks ?? [];
    $booked = $booked ?? \App\Services\BookedSlots::keys();
    $details = $details ?? [];
@endphp
@include('erp.partials.operating-hours')
<script>
    function availabilityCalendar(booked, blocks, manage, details) {
        const pad = n => String(n).padStart(2, '0');
        const iso = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
        const now = new Date();
        const addDays = (d, n) => new Date(d.getFullYear(), d.getMonth(), d.getDate() + n);
        const monday = d => addDays(new Date(d.getFullYear(), d.getMonth(), d.getDate()), -((d.getDay() + 6) % 7));

        return {
            booked, blocks, manage, details, cursor: new Date(now.getFullYear(), now.getMonth(), 1), today: iso(now), months: 6,
            pending: null,
            viewing: null,
            mode: (() => { try { return localStorage.getItem('erp.calendarMode') === 'week' ? 'week' : 'month'; } catch (e) { return 'month'; } })(),
            weekStart: monday(now),
            selected: iso(now),

            setMode(m) {
                this.mode = m;
                try { localStorage.setItem('erp.calendarMode', m); } catch (e) {}
                if (m === 'week') { this.weekStart = monday(new Date(this.selected + 'T00:00:00')); }
            },
            limitEnd() { return new Date(now.getFullYear(), now.getMonth() + this.months + 1, 0); },
            canPrev() {
                return this.mode === 'week' ? this.weekStart > monday(now) : this.cursor > new Date(now.getFullYear(), now.getMonth(), 1);
            },
            canNext() {
                return this.mode === 'week'
                    ? addDays(this.weekStart, 7) <= this.limitEnd()
                    : this.cursor < new Date(now.getFullYear(), now.getMonth() + this.months, 1);
            },
            move(delta) {
                if (this.mode === 'week') {
                    this.weekStart = addDays(this.weekStart, 7 * delta);
                    this.cursor = new Date(this.weekStart.getFullYear(), this.weekStart.getMonth(), 1);
                } else {
                    this.cursor = new Date(this.cursor.getFullYear(), this.cursor.getMonth() + delta, 1);
                    const first = iso(this.cursor);
                    this.selected = this.today.slice(0, 7) === first.slice(0, 7) ? this.today : first;
                }
            },
            label() {
                if (this.mode === 'month') { return this.cursor.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' }); }
                const end = addDays(this.weekStart, 6);
                const sameMonth = end.getMonth() === this.weekStart.getMonth();
                return this.weekStart.toLocaleDateString('en-GB', sameMonth ? { day: 'numeric' } : { day: 'numeric', month: 'short' })
                    + ' – ' + end.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
            },

            /** Opens a day: shows its slots under the month grid. */
            select(day) { if (day.inMonth) { this.selected = day.iso; } },
            openWeek(isoDate) { this.selected = isoDate; this.setMode('week'); },
            selectedDay() { return this.makeDay(new Date(this.selected + 'T00:00:00')); },
            longDate(isoDate) { return new Date(isoDate + 'T00:00:00').toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' }); },

            /** "4 slots · 1 booked" for a month cell. */
            summary(day) {
                const n = day.windows.length;
                const taken = day.windows.filter(w => w.state !== 'available').length;
                return n + (n === 1 ? ' slot' : ' slots') + (taken ? ' · ' + taken + ' booked' : '');
            },

            /** An operating window with the bookings inside it and how much of it is taken: 'available' | 'partial' | 'booked'. */
            windowState(date, w) {
                const from = window.toMin(w.start), to = window.toMin(w.end);
                const bookings = this.booked
                    .map(key => { const [d, r] = key.split('|'); const [s, e] = r.split('-'); return { key, d, start: s, end: e }; })
                    .filter(x => x.d === date && window.toMin(x.start) < to && window.toMin(x.end) > from)
                    .sort((x, y) => window.toMin(x.start) - window.toMin(y.start))
                    .map(x => ({ ...x, blockId: this.manage ? this.blocks[x.key] : undefined, detail: this.details[x.key] || [] }));
                let covered = 0, cursor = from;
                bookings.forEach(x => {
                    const s = Math.max(window.toMin(x.start), cursor), e = Math.min(window.toMin(x.end), to);
                    if (e > s) { covered += e - s; cursor = e; }
                });
                return { ...w, bookings, state: covered === 0 ? 'available' : (covered >= to - from ? 'booked' : 'partial') };
            },

            makeDay(day) {
                const date = iso(day);
                return {
                    iso: date, number: day.getDate(), past: date < this.today,
                    inMonth: this.mode === 'week' || day.getMonth() === this.cursor.getMonth(),
                    weekday: day.toLocaleDateString('en-GB', { weekday: 'short' }),
                    windows: window.slotsFor(date).map(slot => this.windowState(date, slot)),
                };
            },

            /** Month grid, only as many week rows as the month needs. */
            weeks() {
                const first = new Date(this.cursor);
                const lead = (first.getDay() + 6) % 7;
                const daysInMonth = new Date(first.getFullYear(), first.getMonth() + 1, 0).getDate();
                const start = addDays(first, -lead);
                const weeks = [];
                for (let w = 0; w < Math.ceil((lead + daysInMonth) / 7); w++) {
                    weeks.push([...Array(7)].map((_, d) => this.makeDay(addDays(start, w * 7 + d))));
                }
                return weeks;
            },

            weekDays() { return [...Array(7)].map((_, d) => this.makeDay(addDays(this.weekStart, d))); },

            blockable(day, w) { return this.manage && !day.past && w.state !== 'booked'; },

            block(day, w) {
                if (!this.blockable(day, w)) { return; }
                this.pending = { block: true, day, start: w.start, end: w.end, label: w.label };
            },
            unblock(day, booking) {
                if (!this.manage || day.past || !booking.blockId) { return; }
                this.pending = { block: false, day, id: booking.blockId, start: booking.start, end: booking.end, label: booking.start + ' – ' + booking.end };
            },
            cancel() { this.pending = null; },

            /** Multi-line native tooltip for a booked slot: one paragraph per detail entry. */
            tooltipFor(b) {
                if (this.manage && b.blockId) { return 'Blocked by you: click to free it'; }
                if (!b.detail || !b.detail.length) { return 'Booked'; }

                return b.detail.map(d => this.detailLines(d).join('\n')).join('\n\n');
            },
            detailLines(d) {
                const lines = [d.title || 'Booked'];
                if (d.customer) { lines.push('Customer: ' + d.customer); }
                if (d.program) { lines.push('Program: ' + d.program); }
                if (d.instructor) { lines.push('Instructor: ' + d.instructor); }
                if (d.location) { lines.push('Location: ' + d.location); }
                if (d.topic) { lines.push('Topic: ' + d.topic); }
                if (d.reason) { lines.push('Reason: ' + d.reason); }
                return lines;
            },

            /** Opens the read-only details popup for a booked slot (read-only calendars only). */
            view(day, b) {
                if (this.manage || !b.detail || !b.detail.length) { return; }
                this.viewing = { day, booking: b };
            },
            closeView() { this.viewing = null; },
        };
    }
</script>

<div x-data='availabilityCalendar(@json($booked), @json($blocks), @json($manage), @json($details))'>
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <button type="button" @click="move(-1)" :disabled="!canPrev()" class="rounded-md border border-gray-300 px-2.5 py-1 text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-30" :aria-label="mode === 'week' ? 'Previous week' : 'Previous month'">&larr;</button>
            <h3 class="min-w-[10rem] text-center text-base font-semibold text-gray-800" x-text="label()"></h3>
            <button type="button" @click="move(1)" :disabled="!canNext()" class="rounded-md border border-gray-300 px-2.5 py-1 text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-30" :aria-label="mode === 'week' ? 'Next week' : 'Next month'">&rarr;</button>
            <div class="ml-2 inline-flex rounded-md bg-gray-100 p-0.5 text-xs font-medium">
                <button type="button" @click="setMode('month')" class="rounded px-2.5 py-1 transition-colors" :class="mode === 'month' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700'">Month</button>
                <button type="button" @click="setMode('week')" class="rounded px-2.5 py-1 transition-colors" :class="mode === 'week' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700'">Week</button>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-600">
            <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-green-100 ring-1 ring-green-400"></span> Available</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-amber-100 ring-1 ring-amber-400"></span> Partly booked</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-red-100 ring-1 ring-red-400"></span> Booked</span>
            @if ($manage)
                <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-slate-200 ring-1 ring-slate-500"></span> Blocked by you</span>
            @endif
        </div>
    </div>

    {{-- Month: one compact cell per day (dots per slot); click a day for its slots --}}
    <div x-show="mode === 'month'">
        <div class="grid grid-cols-7 gap-1 pb-1 text-center text-[11px] font-semibold uppercase tracking-wide text-gray-500">
            <template x-for="name in ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']" :key="name"><div x-text="name"></div></template>
        </div>
        <template x-for="(week, wi) in weeks()" :key="wi">
            <div class="mb-1 grid grid-cols-7 gap-1">
                <template x-for="day in week" :key="day.iso">
                    <button type="button" @click="select(day)" :disabled="!day.inMonth"
                            class="flex h-14 flex-col rounded-md border p-1.5 text-left transition-colors sm:h-16"
                            :class="!day.inMonth
                                ? 'border-transparent'
                                : [selected === day.iso ? 'border-brand ring-1 ring-brand bg-white' : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50', day.past ? 'opacity-50' : '']">
                        <template x-if="day.inMonth">
                            <div class="flex h-full w-full flex-col">
                                <span class="text-xs" :class="day.iso === today ? 'self-start rounded-full bg-brand px-1.5 font-semibold text-white' : 'font-medium text-gray-700'" x-text="day.number"></span>
                                <div class="mt-auto flex flex-wrap items-center gap-1">
                                    <template x-for="w in day.windows" :key="w.value">
                                        <span class="h-2 w-2 rounded-full"
                                              :class="{ 'bg-green-400': w.state === 'available', 'bg-amber-400': w.state === 'partial', 'bg-red-400': w.state === 'booked' }"></span>
                                    </template>
                                    <span x-show="!day.windows.length" class="text-[10px] uppercase text-gray-300">Closed</span>
                                </div>
                                <span x-show="day.windows.length" class="hidden truncate text-[10px] text-gray-500 md:block" x-text="summary(day)"></span>
                            </div>
                        </template>
                    </button>
                </template>
            </div>
        </template>

        {{-- Slots of the selected day --}}
        <div class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-3">
            <template x-if="selected">
                <div x-data="{ get day() { return selectedDay() } }">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-gray-800" x-text="longDate(selected)"></p>
                        <button type="button" @click="openWeek(selected)" class="text-xs font-medium text-brand hover:underline">View week &rarr;</button>
                    </div>
                    <p x-show="!day.windows.length" class="text-xs text-gray-500">Closed on this day.</p>
                    <div class="grid gap-1.5 sm:grid-cols-2 lg:grid-cols-4">
                        @include('erp.partials.availability-day-slots')
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Week: seven columns with every slot --}}
    <div x-show="mode === 'week'" x-cloak class="overflow-x-auto">
        <div class="grid min-w-[44rem] grid-cols-7 gap-1">
            <template x-for="day in weekDays()" :key="day.iso">
                <div class="rounded-md border border-gray-200 bg-white p-1" :class="day.past ? 'opacity-50' : ''">
                    <div class="mb-1 flex items-center justify-between px-0.5 text-xs">
                        <span class="font-semibold uppercase tracking-wide text-gray-500" x-text="day.weekday"></span>
                        <span :class="day.iso === today ? 'rounded-full bg-brand px-1.5 font-semibold text-white' : 'font-medium text-gray-700'" x-text="day.number"></span>
                    </div>
                    <p x-show="!day.windows.length" class="px-0.5 text-[10px] uppercase text-gray-300">Closed</p>
                    <div class="space-y-1">
                        @include('erp.partials.availability-day-slots')
                    </div>
                </div>
            </template>
        </div>
    </div>

    @if ($manage)
        <div x-show="pending" x-cloak @keydown.escape.window="cancel()" @click.self="cancel()" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <template x-if="pending">
                <form method="POST" :action="pending.block ? @js($blockUrl) : @js($unblockUrl).replace('__ID__', pending.id)" class="w-full max-w-sm space-y-4 rounded-xl bg-white p-5 shadow-2xl">
                    @csrf
                    <input type="hidden" name="_method" :value="pending.block ? 'POST' : 'DELETE'">
                    <input type="hidden" name="date" :value="pending.day.iso">
                    <h4 class="text-base font-semibold text-gray-800" x-text="pending.block ? 'Block a time' : 'Free this time?'"></h4>
                    <p class="text-sm text-gray-600"><span x-text="pending.day.iso"></span> · <span x-text="pending.block ? 'open ' + pending.label : pending.label"></span></p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600">From</label>
                            <input type="time" name="start_time" required x-model="pending.start" :readonly="!pending.block" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Until</label>
                            <input type="time" name="end_time" required x-model="pending.end" :readonly="!pending.block" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                        </div>
                    </div>
                    <p class="text-xs text-gray-500" x-text="pending.block ? 'Set the time you are busy. It will show as booked to customers and cannot be chosen for a meeting.' : 'It will become available for booking again.'"></p>
                    <div x-show="pending.block">
                        <label class="block text-xs font-medium text-gray-600">Reason (only you see this)</label>
                        <input type="text" name="reason" maxlength="255" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm" placeholder="e.g. Other engagement">
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="cancel()" class="rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Cancel</button>
                        <button type="submit" class="rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white shadow-sm" x-text="pending.block ? 'Block time' : 'Free time'"></button>
                    </div>
                </form>
            </template>
        </div>
    @endif

    <div x-show="viewing" x-cloak @keydown.escape.window="closeView()" @click.self="closeView()" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <template x-if="viewing">
            <div class="w-full max-w-sm space-y-3 rounded-xl bg-white p-5 shadow-2xl">
                <div class="flex items-start justify-between gap-2">
                    <h4 class="text-base font-semibold text-gray-800">Booking details</h4>
                    <button type="button" @click="closeView()" class="text-gray-400 hover:text-gray-600" aria-label="Close">&times;</button>
                </div>
                <p class="text-sm text-gray-600">
                    <span x-text="viewing.day.iso"></span> &middot; <span x-text="viewing.booking.start + ' – ' + viewing.booking.end"></span>
                </p>
                <template x-for="(d, i) in viewing.booking.detail" :key="i">
                    <div class="rounded-lg border border-gray-200 p-3 text-sm space-y-1">
                        <p class="font-semibold text-gray-800" x-text="d.title || 'Booked'"></p>
                        <p x-show="d.customer" class="text-gray-600">Customer: <span class="text-gray-800" x-text="d.customer"></span></p>
                        <p x-show="d.program" class="text-gray-600">Program: <span class="text-gray-800" x-text="d.program"></span></p>
                        <p x-show="d.instructor" class="text-gray-600">Instructor: <span class="text-gray-800" x-text="d.instructor"></span></p>
                        <p x-show="d.location" class="text-gray-600">Location: <span class="text-gray-800" x-text="d.location"></span></p>
                        <p x-show="d.topic" class="text-gray-600">Topic: <span class="text-gray-800" x-text="d.topic"></span></p>
                        <p x-show="d.reason" class="text-gray-600">Reason: <span class="text-gray-800" x-text="d.reason"></span></p>
                        <a x-show="d.url" :href="d.url" class="inline-block text-sm font-medium text-brand hover:underline">View session &rarr;</a>
                    </div>
                </template>
                <div class="flex justify-end">
                    <button type="button" @click="closeView()" class="rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Close</button>
                </div>
            </div>
        </template>
    </div>
</div>
