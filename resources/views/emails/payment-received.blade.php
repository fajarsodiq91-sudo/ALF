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

@php $remaining = $session->payments->reject->isPaid()->sum('amount'); @endphp
@if ($remaining > 0)
Remaining balance: **{{ \App\Services\SessionPaymentPlan::rupiah($remaining) }}**. We will send you an invoice when it is due.
@else
Your program is now fully paid.
@endif

Thank you,<br>
PT Alfajar Logic Futura
</x-mail::message>
