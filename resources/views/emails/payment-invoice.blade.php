<x-mail::message>
# Invoice {{ $invoiceNumbers }}

Dear {{ $session->customer->name }},

Here is the invoice for your program at PT Alfajar Logic Futura.

**Program:** {{ $session->program->name }}<br>
**Program fee:** {{ \App\Services\SessionPaymentPlan::rupiah($session->fee) }}

<x-mail::table>
| Invoice | Payment | Due | Amount |
|:--|:--|:--|--:|
@foreach ($payments as $payment)
| {{ $payment->invoiceNumber() }} | {{ $payment->label }} | {{ $payment->dueLabel() }} | {{ \App\Services\SessionPaymentPlan::rupiah($payment->amount) }} |
@endforeach
| | | **Amount due** | **{{ \App\Services\SessionPaymentPlan::rupiah($total) }}** |
</x-mail::table>

@if ($payments->contains->is_registration_fee)
The registration fee is paid once, is non-refundable, and is not part of the program fee.

@endif
@if ($bankAccount)
Please transfer the amount due to the following account:

<x-mail::panel>
{!! implode("  \n", array_map('e', preg_split('/\R/', trim($bankAccount)))) !!}
</x-mail::panel>

Let us know once the transfer is done. For any question, contact us: [admin@alfajarlogic.com](mailto:admin@alfajarlogic.com) or [WhatsApp](https://wa.me/6282125298452).
@else
Please make the transfer and let us know once it is done. Contact us for our account details or any question: [admin@alfajarlogic.com](mailto:admin@alfajarlogic.com) or [WhatsApp](https://wa.me/6282125298452).
@endif
We will confirm your payment by email as soon as we have received it.

<x-mail::button :url="$loginUrl">
Open your portal
</x-mail::button>

Thank you,<br>
PT Alfajar Logic Futura
</x-mail::message>
