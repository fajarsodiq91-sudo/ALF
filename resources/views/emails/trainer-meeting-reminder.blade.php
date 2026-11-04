<x-mail::message>
# {{ $hourBefore ? 'You teach in 1 hour' : 'You teach tomorrow' }}

Hello {{ $trainer->name }},

{{ $hourBefore ? 'This is a reminder that the meeting below starts in about an hour.' : 'This is a reminder that you have the meeting below tomorrow.' }}

<x-mail::table>
| | |
|:--|:--|
| Program | {{ $meeting->session->program?->name ?? '-' }} |
| Customer | {{ $meeting->session->customer?->name ?? '-' }} |
| Date | {{ $meeting->meeting_date->format('D, d M Y') }} |
| Time | {{ $meeting->timeRange() ?? 'No time set' }} |
| Location | {{ $meeting->location ?? $meeting->session->location ?? '-' }} |
@if ($meeting->topic)
| Topic | {{ $meeting->topic }} |
@endif
</x-mail::table>

<x-mail::button :url="$loginUrl">
Open the ERP
</x-mail::button>

Regards,<br>
PT Alfajar Logic Futura
</x-mail::message>
