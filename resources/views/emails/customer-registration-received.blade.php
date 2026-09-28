<x-mail::message>
# Thank you, {{ $customer->name }}!

We have received your registration at PT Alfajar Logic Futura.

**Your registration is now waiting for approval.** Our team will review it and set up your program and schedule.
Once it is approved, you will receive another email with your customer ID and a link to log in.

@php $requested = $customer->requestedProgramSummaries(); @endphp
@if ($requested)
**Programs and dates you asked for** (our team will confirm the final schedule):

@foreach ($requested as $entry)
- **{{ $entry['program']->name }}**
@foreach ($entry['meetings'] as $meeting)
    - {{ $meeting['label'] }}
@endforeach
@endforeach

@endif
If you have questions in the meantime, just reply to this email.

Regards,<br>
PT Alfajar Logic Futura
</x-mail::message>
