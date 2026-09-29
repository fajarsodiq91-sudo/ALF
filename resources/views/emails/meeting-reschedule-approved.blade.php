<x-mail::message>
# Your reschedule request is approved

Hello {{ $rescheduleRequest->customer->name }},

Your request to move your **{{ $meeting->session->program->name }}** meeting has been approved.

<x-mail::table>
| | |
|:--|:--|
| Previous schedule | ~~{{ $originalLabel }}~~ |
| New schedule | **{{ $meeting->meeting_date->format('D, d M Y') }}, {{ $meeting->timeRange() }}** |
@if ($meeting->location)
| Location | {{ $meeting->location }} |
@endif
</x-mail::table>

<x-mail::button :url="$loginUrl">
View my schedule
</x-mail::button>

Regards,<br>
PT Alfajar Logic Futura
</x-mail::message>
