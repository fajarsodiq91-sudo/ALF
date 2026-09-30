{{-- Makes the operating hours available to the meeting forms: window.operatingHours plus small helpers. --}}
<script>
    window.operatingHours = @json(\App\Services\OperatingHours::forBrowser());
    window.dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    /** Slots open on the weekday of a YYYY-MM-DD date. Corporate-only slots need allowCorporate. */
    window.slotsFor = function (date, allowCorporate) {
        if (!date) { return []; }
        const slots = window.operatingHours.days[new Date(date + 'T00:00:00').getDay()] || [];
        return allowCorporate ? slots : slots.filter(s => !s.corporate);
    };

    window.toMin = t => { const [h, m] = String(t).split(':'); return parseInt(h, 10) * 60 + parseInt(m || 0, 10); };
    window.fmtMin = n => String(Math.floor(n / 60)).padStart(2, '0') + ':' + String(n % 60).padStart(2, '0');

    /** Whether the range overlaps one of the booked "YYYY-MM-DD|HH:MM-HH:MM" keys. */
    window.overlapsBooked = function (keys, date, start, end) {
        return keys.some(key => {
            const [d, range] = key.split('|');
            if (d !== date) { return false; }
            const [s, e] = range.split('-');
            return window.toMin(s) < window.toMin(end) && window.toMin(e) > window.toMin(start);
        });
    };

    /** What can be booked on a date: the whole windows, or (with a session length) start times inside them. */
    window.candidateSlots = function (date, minutes, allowCorporate) {
        const windows = window.slotsFor(date, allowCorporate);
        if (!minutes) { return windows; }
        const out = [];
        windows.forEach(w => {
            for (let s = window.toMin(w.start); s + minutes <= window.toMin(w.end); s += window.operatingHours.step) {
                const start = window.fmtMin(s), end = window.fmtMin(s + minutes);
                out.push({ value: start + '-' + end, start, end, label: start + ' – ' + end });
            }
        });
        return out;
    };

    /** A hint for the date, or '' when the date is fine (or unknown). */
    window.hoursHint = function (date, allowCorporate) {
        if (!date || !window.operatingHours.enforced) { return ''; }
        return window.slotsFor(date, allowCorporate).length ? '' : window.dayNames[new Date(date + 'T00:00:00').getDay()] + ' is not an operating day.';
    };

    /** Fills start and end from a "HH:MM-HH:MM" slot value, or clears them. */
    window.pickSlot = function (meeting, value) {
        const [start, end] = value ? value.split('-') : ['', ''];
        meeting.start_time = start;
        meeting.end_time = end;
    };

    /** When the date changes on a strict schedule, drop a time that no longer matches a slot. */
    window.syncSlot = function (meeting) {
        if (!window.operatingHours.enforced) { return; }
        const match = window.slotsFor(meeting.meeting_date).some(s => s.start === meeting.start_time && s.end === meeting.end_time);
        if (!match) { meeting.start_time = ''; meeting.end_time = ''; }
    };
</script>
