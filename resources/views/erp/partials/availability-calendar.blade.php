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

            /** 'available' | 'booked' | 'blocked' (blocked by the company; only tells apart from booked when managing). */
            stateOf(date, slot) {
                const key = date + '|' + slot.value;
                if (this.manage && this.blocks[key]) { return 'blocked'; }
                return this.booked.includes(key) ? 'booked' : 'available';
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
                            slots: window.slotsFor(date).map(slot => ({ ...slot, state: this.stateOf(date, slot) })),
                        });
                    }
                    weeks.push(days);
                }
                return weeks;
            },

            clickable(day, slot) { return this.manage && !day.past && (slot.state === 'available' || slot.state === 'blocked'); },

            pick(day, slot) {
                if (!this.clickable(day, slot)) { return; }
                this.pending = { day, slot, block: slot.state === 'available', id: this.blocks[day.iso + '|' + slot.value] };
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
                            <div class="mt-1 space-y-0.5">
                                <template x-for="slot in day.slots" :key="slot.value">
                                    <button type="button" @click="pick(day, slot)" :disabled="!clickable(day, slot)"
                                            :title="slot.state === 'booked' ? 'Booked' : (slot.state === 'blocked' ? 'Blocked by you: click to free it' : (manage ? 'Available: click to block' : 'Available'))"
                                            class="block w-full rounded px-1 py-0.5 text-left text-[11px] leading-tight ring-1"
                                            :class="{
                                                'bg-green-100 text-green-800 ring-green-300': slot.state === 'available',
                                                'hover:bg-green-200 hover:ring-green-500 cursor-pointer': slot.state === 'available' && clickable(day, slot),
                                                'bg-red-100 text-red-700 ring-red-300': slot.state === 'booked',
                                                'bg-slate-200 text-slate-700 ring-slate-400 hover:bg-slate-300 cursor-pointer': slot.state === 'blocked',
                                                'cursor-default': !clickable(day, slot),
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

    @if ($manage)
        <div x-show="pending" x-cloak @keydown.escape.window="cancel()" @click.self="cancel()" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <template x-if="pending">
                <form method="POST" :action="pending.block ? @js($blockUrl) : @js($unblockUrl).replace('__ID__', pending.id)" class="w-full max-w-sm space-y-4 rounded-xl bg-white p-5 shadow-2xl">
                    @csrf
                    <input type="hidden" name="_method" :value="pending.block ? 'POST' : 'DELETE'">
                    <input type="hidden" name="date" :value="pending.day.iso">
                    <input type="hidden" name="start_time" :value="pending.slot.start">
                    <input type="hidden" name="end_time" :value="pending.slot.end">
                    <h4 class="text-base font-semibold text-gray-800" x-text="pending.block ? 'Block this slot?' : 'Free this slot?'"></h4>
                    <p class="text-sm text-gray-600"><span x-text="pending.day.iso"></span> · <span x-text="pending.slot.label"></span></p>
                    <p class="text-xs text-gray-500" x-text="pending.block ? 'It will show as booked to customers and cannot be chosen for a meeting.' : 'It will become available for booking again.'"></p>
                    <div x-show="pending.block">
                        <label class="block text-xs font-medium text-gray-600">Reason (only you see this)</label>
                        <input type="text" name="reason" maxlength="255" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm" placeholder="e.g. Other engagement">
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="cancel()" class="rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Cancel</button>
                        <button type="submit" class="rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white shadow-sm" x-text="pending.block ? 'Block slot' : 'Free slot'"></button>
                    </div>
                </form>
            </template>
        </div>
    @endif
</div>
