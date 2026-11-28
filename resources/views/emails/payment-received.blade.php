<x-mail::message>
# Thank you for your payment

Dear {{ $session->customer->name }},

We have received and confirmed your payment. Thank you!

<x-mail::table>
| | |
|:--|:--|
| Invoice | {{ $payment->invoiceNumber() }} |
| Program | {{ $session->program->name }} |
| Payment | {{ $payment->label }} |
| Amount received | **{{ \App\Services\SessionPaymentPlan::rupiah($payment->amount) }}** |
| Paid on | {{ $payment->paid_date?->format('d M Y') }} |
</x-mail::table>

@if ($dueNow->isNotEmpty())
## Still to be paid now

<x-mail::table>
| Invoice | Payment | Amount |
|:--|:--|--:|
@foreach ($dueNow as $item)
| {{ $item->invoiceNumber() }} | {{ $item->label }} | **{{ \App\Services\SessionPaymentPlan::rupiah($item->amount) }}** |
@endforeach
</x-mail::table>

@if ($session->hasUnpaidRegistrationFee())
Your program starts once the registration fee is paid.

@endif
@if ($bankAccount)
Please complete this payment to the following account:

<x-mail::panel>
{!! implode("  \n", array_map('e', preg_split('/\R/', trim($bankAccount)))) !!}
</x-mail::panel>
@else
Please complete this payment. Contact us for our account details: [admin@alfajarlogic.com](mailto:admin@alfajarlogic.com) or [WhatsApp](https://wa.me/6282125298452).
@endif

@endif
@if ($upcoming->isNotEmpty())
## Due later

@foreach ($upcoming as $item)
- {{ $item->label }}: {{ \App\Services\SessionPaymentPlan::rupiah($item->amount) }} ({{ $item->dueLabel() }})
@endforeach

We will send you an invoice when it is due.

@endif
@if ($dueNow->isEmpty() && $upcoming->isEmpty())
Your program is now fully paid.

@endif
Thank you,<br>
PT Alfajar Logic Futura
</x-mail::message>
