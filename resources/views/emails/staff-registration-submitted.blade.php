<x-mail::message>
# New registration waiting for approval

**{{ $customer->name }}** has just submitted their registration. Please review it, then approve or reject it.

<x-mail::table>
| | |
|:--|:--|
| Name | {{ $customer->name }} |
| Type | {{ \App\Services\MasterData::label('customer_type', $customer->customer_type) }} |
| Email | {{ $customer->email ?: '—' }} |
| Phone | {{ $customer->phone ?: '—' }} |
| City | {{ $customer->city ?: '—' }} |
</x-mail::table>

@if ($requested)
**Programs and dates they asked for:**

@foreach ($requested as $entry)
- **{{ $entry['program']->name }}**
@foreach ($entry['meetings'] as $meeting)
    - {{ $meeting['label'] }}
@endforeach
@endforeach

@else
They have not chosen a program yet. You can add one while reviewing.

@endif
<x-mail::button :url="$reviewUrl">
Review registration
</x-mail::button>

Regards,<br>
PT Alfajar Logic Futura
</x-mail::message>
