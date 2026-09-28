{{--
    Month calendar of the company's operating hours: green = available, red = booked (by a customer or blocked by the company).
    Read-only by default. With $manage the company can click a green slot to block it, or a grey (blocked) slot to free it.
    Optional: $blocks (key => id, from BlockedSlot), $blockUrl (POST), $unblockUrl (DELETE, with "__ID__" as the placeholder).
--}}
@php
    $manage = $manage ?? false;
    $blocks = $blocks ?? [];
    $booked = $booked ?? \App\Services\BookedSlots::keys();
@endphp
@include('erp.partials.operating-hours')
<script>
    function availabilityCalendar(booked, blocks, manage) {
        const pad = n => String(n).padStart(2, '0');
        const iso = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
        const now = new Date();

        return {
            booked, blocks, manage, cursor: new Date(now.getFullYear(), now.getMonth(), 1), today: iso(now), months: 6,
            pending: null,

            canPrev() { return this.cursor > new Date(now.getFullYear(), now.getMonth(), 1); },
            canNext() { return this.cursor < new Date(now.getFullYear(), now.getMonth() + this.months, 1); },
            move(delta) { this.cursor = new Date(this.cursor.getFullYear(), this.cursor.getMonth() + delta, 1); },
            monthLabel() { return this.cursor.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' }); },

            /** An operating window with the bookings inside it and how much of it is taken: 'available' | 'partial' | 'booked'. */
            windowState(date, w) {
                const from = window.toMin(w.start), to = window.toMin(w.end);
                const bookings = this.booked
                    .map(key => { const [d, r] = key.split('|'); const [s, e] = r.split('-'); return { key, d, start: s, end: e }; })
                    .filter(x => x.d === date && window.toMin(x.start) < to && window.toMin(x.end) > from)
                    .sort((x, y) => window.toMin(x.start) - window.toMin(y.start))
                    .map(x => ({ ...x, blockId: this.manage ? this.blocks[x.key] : undefined }));
                let covered = 0, cursor = from;
                bookings.forEach(x => {
                    const s = Math.max(window.toMin(x.start), cursor), e = Math.min(window.toMin(x.end), to);
                    if (e > s) { covered += e - s; cursor = e; }
                });
                return { ...w, bookings, state: covered === 0 ? 'available' : (covered >= to - from ? 'booked' : 'partial') };
            },

            weeks() {
                const first = new Date(this.cursor);
                const start = new Date(first.getFullYear(), first.getMonth(), 1 - (first.getDay() + 6) % 7);
                const weeks = [];
                for (let w = 0; w < 6; w++) {
                    const days = [];
                    for (let d = 0; d < 7; d++) {
                        const day = new Date(start.getFullYear(), start.getMonth(), start.getDate() + w * 7 + d);
                        const date = iso(day);
                        days.push({
                            iso: date, number: day.getDate(), inMonth: day.getMonth() === this.cursor.getMonth(), past: date < this.today,
                            windows: window.slotsFor(date).map(slot => this.windowState(date, slot)),
                        });
                    }
                    weeks.push(days);
                }
                return weeks;
            },

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
        };
    }
</script>

<div x-data='availabilityCalendar(@json($booked), @json($blocks), @json($manage))'>
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <button type="button" @click="move(-1)" :disabled="!canPrev()" class="rounded-md border border-gray-300 px-2.5 py-1 text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-30" aria-label="Previous month">&larr;</button>
            <h3 class="min-w-[9rem] text-center text-base font-semibold text-gray-800" x-text="monthLabel()"></h3>
            <button type="button" @click="move(1)" :disabled="!canNext()" class="rounded-md border border-gray-300 px-2.5 py-1 text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-30" aria-label="Next month">&rarr;</button>
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

    <div class="overflow-x-auto">
        <div class="min-w-[44rem]">
            <div class="grid grid-cols-7 gap-1 pb-1 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                <template x-for="name in ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']" :key="name"><div x-text="name"></div></template>
            </div>
            <template x-for="(week, w) in weeks()" :key="w">
                <div class="mb-1 grid grid-cols-7 gap-1">
                    <template x-for="day in week" :key="day.iso">
                        <div class="min-h-[5.5rem] rounded-md border p-1" :class="[day.inMonth ? 'border-gray-200 bg-white' : 'border-gray-100 bg-gray-50', day.past ? 'opacity-50' : '']">
                            <div class="flex items-center justify-between px-0.5 text-xs">
                                <span :class="day.iso === today ? 'rounded-full bg-brand px-1.5 font-semibold text-white' : (day.inMonth ? 'font-medium text-gray-700' : 'text-gray-400')" x-text="day.number"></span>
                                <span x-show="!day.slots.length && day.inMonth && !day.past" class="text-[10px] uppercase text-gray-300">Closed</span>
                            </div>
                            <div class="mt-1 space-y-1">
                                <template x-for="w in day.windows" :key="w.value">
                                    <div>
                                        <button type="button" @click="block(day, w)" :disabled="!blockable(day, w)"
                                                :title="w.state === 'booked' ? 'Fully booked' : (w.state === 'partial' ? 'Partly booked' : 'Available') + (blockable(day, w) ? ': click to block a time' : '')"
                                                class="block w-full rounded px-1 py-0.5 text-left text-[11px] leading-tight ring-1"
                                                :class="{
                                                    'bg-green-100 text-green-800 ring-green-300': w.state === 'available',
                                                    'bg-amber-100 text-amber-800 ring-amber-300': w.state === 'partial',
                                                    'bg-red-100 text-red-700 ring-red-300': w.state === 'booked',
                                                    'cursor-pointer hover:ring-2': blockable(day, w),
                                                    'cursor-default': !blockable(day, w),
                                                }"
                                                x-text="w.label"></button>
                                        <template x-for="b in w.bookings" :key="b.key">
                                            <button type="button" @click="unblock(day, b)" :disabled="!(manage && b.blockId && !day.past)"
                                                    :title="b.blockId ? 'Blocked by you: click to free it' : 'Booked'"
                                                    class="mt-0.5 block w-full rounded px-1 text-left text-[10px] leading-tight"
                                                    :class="b.blockId ? 'bg-slate-200 text-slate-700 cursor-pointer hover:bg-slate-300' : 'bg-red-50 text-red-600 cursor-default'"
                                                    x-text="b.start + '–' + b.end + (b.blockId ? ' blocked' : ' booked')"></button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
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
</div>
