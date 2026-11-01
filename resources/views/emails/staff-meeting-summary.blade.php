<x-mail::message>
# Meetings today and tomorrow

@foreach (['Today' => $today, 'Tomorrow' => $tomorrow] as $label => $meetings)
## {{ $label }}

@if ($meetings->isEmpty())
No meetings.

@else
<x-mail::table>
| Time | Program | Customer | Location |
|:--|:--|:--|:--|
@foreach ($meetings as $meeting)
| {{ $meeting->timeRange() ?? 'No time set' }} | {{ $meeting->session->program?->name ?? '-' }} | {{ $meeting->session->customer?->name ?? '-' }} | {{ $meeting->location ?? $meeting->session->location ?? '-' }} |
@endforeach
</x-mail::table>

@endif
@endforeach
<x-mail::button :url="$scheduleUrl">
Open training
</x-mail::button>

Regards,<br>
PT Alfajar Logic Futura
</x-mail::message>
