<x-mail::message>
# Reschedule request

**{{ $rescheduleRequest->customer->name }}** asked to move their **{{ $meeting->session->program->name }}** meeting.

<x-mail::table>
| | |
|:--|:--|
| Current schedule | {{ $meeting->meeting_date->format('D, d M Y') }}, {{ $meeting->timeRange() ?? 'no time set' }} |
| Requested schedule | **{{ $rescheduleRequest->requestedLabel() }}** |
</x-mail::table>

@if ($rescheduleRequest->reason)
**Reason:** {{ $rescheduleRequest->reason }}

@endif
Open the session to approve or reject the request. The customer is told by email once you decide.

<x-mail::button :url="$reviewUrl">
Review request
</x-mail::button>

Regards,<br>
PT Alfajar Logic Futura
</x-mail::message>
