<x-mail::message>
# Your leave request was {{ $approved ? 'approved' : 'rejected' }}

Hello {{ $leave->employee->name }},

@if ($approved)
Your leave request has been **approved**.
@else
We are unable to approve your leave request.
@endif

<x-mail::table>
| | |
|:--|:--|
| Type | {{ \App\Services\MasterData::label('leave_type', $leave->leave_type) }} |
| From | {{ $leave->start_date->format('D, d M Y') }} |
| To | {{ $leave->end_date->format('D, d M Y') }} |
| Working days | {{ $leave->days }} |
</x-mail::table>

Please speak to HR if you have any questions.

Regards,<br>
PT Alfajar Logic Futura
</x-mail::message>
