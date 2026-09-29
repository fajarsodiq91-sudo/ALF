<x-mail::message>
# About your reschedule request

Hello {{ $rescheduleRequest->customer->name }},

We are unable to approve your request to move your **{{ $meeting->session->program->name }}** meeting to {{ $rescheduleRequest->requestedLabel() }}.

Your meeting stays as originally scheduled:

<x-mail::table>
| | |
|:--|:--|
| Schedule | **{{ $meeting->meeting_date->format('D, d M Y') }}, {{ $meeting->timeRange() }}** |
@if ($meeting->location)
| Location | {{ $meeting->location }} |
@endif
</x-mail::table>

If you still need to reschedule, please log in to the portal and submit a new request, or reply to this email.

<x-mail::button :url="$loginUrl">
View my schedule
</x-mail::button>

Regards,<br>
PT Alfajar Logic Futura
</x-mail::message>
