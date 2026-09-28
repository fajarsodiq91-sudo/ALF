{{-- Makes the operating hours available to the meeting forms: window.operatingHours plus small helpers. --}}
<script>
    window.operatingHours = @json(\App\Services\OperatingHours::forBrowser());
    window.dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    /** Slots open on the weekday of a YYYY-MM-DD date. */
    window.slotsFor = function (date) {
        if (!date) { return []; }
        return window.operatingHours.days[new Date(date + 'T00:00:00').getDay()] || [];
    };

    /** A hint for the date, or '' when the date is fine (or unknown). */
    window.hoursHint = function (date) {
        if (!date || !window.operatingHours.enforced) { return ''; }
        return window.slotsFor(date).length ? '' : window.dayNames[new Date(date + 'T00:00:00').getDay()] + ' is not an operating day.';
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
