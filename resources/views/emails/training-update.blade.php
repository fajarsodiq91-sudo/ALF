<x-mail::message>
# {{ $heading }}

Hello {{ $recipient->name }},

{{ $message }}

@if ($details)
<x-mail::table>
| | |
|:--|:--|
@foreach ($details as $label => $value)
| {{ $label }} | {{ $value }} |
@endforeach
</x-mail::table>

@endif
<x-mail::button :url="$loginUrl">
View my schedule
</x-mail::button>

Regards,<br>
PT Alfajar Logic Futura
</x-mail::message>
