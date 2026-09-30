{{--
    Weekly calendar of operating hours (Monday to Sunday, time down the side).
    Expects an Alpine scope with `days`: { 1: [{start: "20:00", end: "21:30"}], … 7: [] } keyed by ISO weekday.
--}}
<script>
    if (!window.weekRange) {
        window.toMinutes = t => { const [h, m] = String(t).split(':'); return parseInt(h, 10) * 60 + parseInt(m || 0, 10); };
        window.validSlot = s => !!s.start && !!s.end && window.toMinutes(s.end) > window.toMinutes(s.start);
        /** The visible time span, rounded out to whole hours. */
        window.weekRange = days => {
            let min = Infinity, max = -Infinity;
            Object.values(days).forEach(list => list.filter(window.validSlot).forEach(s => { min = Math.min(min, window.toMinutes(s.start)); max = Math.max(max, window.toMinutes(s.end)); }));
            return min === Infinity ? { start: 8 * 60, end: 17 * 60 } : { start: Math.floor(min / 60) * 60, end: Math.ceil(max / 60) * 60 };
        };
        window.weekHours = days => { const r = window.weekRange(days), out = []; for (let m = r.start; m <= r.end; m += 60) { out.push(m); } return out; };
        window.weekTop = (minutes, days) => { const r = window.weekRange(days); return ((minutes - r.start) / (r.end - r.start) * 100) + '%'; };
        window.slotStyle = (slot, days) => {
            const r = window.weekRange(days), span = r.end - r.start;
            return 'top:' + ((window.toMinutes(slot.start) - r.start) / span * 100) + '%;height:' + ((window.toMinutes(slot.end) - window.toMinutes(slot.start)) / span * 100) + '%';
        };
        window.weekDayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    }
</script>

<div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
    <div class="min-w-[40rem]">
        <div class="grid grid-cols-[3.5rem_repeat(7,minmax(0,1fr))] border-b border-gray-200 bg-gray-50 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
            <div></div>
            <template x-for="(name, i) in weekDayNames" :key="name">
                <div class="py-2" :class="days[i + 1].some(validSlot) ? 'text-brand' : 'text-gray-400'" x-text="name"></div>
            </template>
        </div>

        <div class="grid grid-cols-[3.5rem_repeat(7,minmax(0,1fr))]">
            <div class="relative h-[26rem] border-r border-gray-100">
                <template x-for="minutes in weekHours(days)" :key="minutes">
                    <span class="absolute right-2 -translate-y-1/2 text-[10px] text-gray-400" :style="'top:' + weekTop(minutes, days)" x-text="String(Math.floor(minutes / 60)).padStart(2, '0') + ':00'"></span>
                </template>
            </div>

            <template x-for="(name, i) in weekDayNames" :key="name">
                <div class="relative h-[26rem] border-r border-gray-100 last:border-r-0" :class="days[i + 1].some(validSlot) ? 'bg-white' : 'bg-gray-50'">
                    <template x-for="minutes in weekHours(days)" :key="minutes">
                        <div class="absolute inset-x-0 border-t border-gray-100" :style="'top:' + weekTop(minutes, days)"></div>
                    </template>
                    <span x-show="!days[i + 1].some(validSlot)" class="absolute inset-x-0 top-2 text-center text-[10px] uppercase tracking-wide text-gray-300">Closed</span>
                    <template x-for="(slot, k) in days[i + 1].filter(validSlot)" :key="k">
                        <div class="absolute inset-x-1 flex items-center justify-center overflow-hidden rounded-md px-0.5 text-center text-[10px] font-medium leading-tight text-white shadow-sm ring-1"
                             :class="slot.corporate ? 'bg-gradient-to-b from-purple-500 to-purple-700 ring-purple-800/40' : 'bg-gradient-to-b from-brand-light to-brand-dark ring-brand-dark/40'"
                             :style="slotStyle(slot, days)" :title="slot.start + ' – ' + slot.end + (slot.corporate ? ' (corporate training only)' : '')">
                            <span x-text="slot.start + '–' + slot.end"></span>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>
