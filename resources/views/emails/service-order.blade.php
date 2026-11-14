<x-mail::message>
# {{ $heading }}

@foreach ($lines as $line)
{{ $line }}

@endforeach
@if ($details)
<x-mail::table>
| | |
|:--|:--|
@foreach ($details as $label => $value)
| {{ $label }} | {{ $value }} |
@endforeach
</x-mail::table>

@endif
@if ($bankAccount)
**Pay to:**<br>
{!! nl2br(e($bankAccount)) !!}

@endif
@if ($buttonUrl)
<x-mail::button :url="$buttonUrl">
{{ $buttonLabel }}
</x-mail::button>

@endif
Regards,<br>
PT Alfajar Logic Futura
</x-mail::message>
