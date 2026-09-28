<x-mail::message>
# Welcome, {{ $customer->name }}!

Your registration at PT Alfajar Logic Futura has been **approved**.

## Your details

<x-mail::table>
| | |
|:--|:--|
| Customer ID | **{{ $customer->customer_code }}** |
| Type | {{ \App\Services\MasterData::label('customer_type', $customer->customer_type) }} |
| Contact person | {{ $customer->contact_person ?: '—' }} |
| Email | {{ $customer->email ?: '—' }} |
| Phone | {{ $customer->phone ?: '—' }} |
| City | {{ $customer->city ?: '—' }} |
| Address | {{ $customer->address ?: '—' }} |
</x-mail::table>

## Your programs

@forelse ($sessions as $session)
### {{ $session->program->name }} ({{ \App\Services\MasterData::label('program_type', $session->program->program_type) }})

Delivery: {{ \App\Services\MasterData::label('delivery_mode', $session->delivery_mode) }}@if ($session->location) · {{ $session->location }}@endif

@foreach ($session->meetings as $meeting)
- {{ $meeting->meeting_date->format('l, d M Y') }}@if ($meeting->timeRange()), {{ $meeting->timeRange() }}@endif @if ($meeting->location ?? $session->location) at {{ $meeting->location ?? $session->location }}@endif @if ($meeting->topic) ({{ $meeting->topic }})@endif

@endforeach
@empty
Your program schedule will be shared soon.
@endforelse

## Log in to your customer portal

There you can see your meeting schedule and progress, open your learning materials and certificate, and upload the project you build so we can add it to our portfolio.

<x-mail::button :url="$loginUrl">
Log in
</x-mail::button>

- Username: **{{ $customer->customer_code }}**
- Password: **{{ $customer->customer_code }}** (the same as your customer ID)

For your security you will be asked to choose a new password the first time you log in.

Regards,<br>
PT Alfajar Logic Futura
</x-mail::message>
