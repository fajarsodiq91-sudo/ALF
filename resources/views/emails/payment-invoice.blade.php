<x-mail::message>
# Invoice {{ $payment->invoiceNumber() }}

Dear {{ $session->customer->name }},

Here is the invoice for your program at PT Alfajar Logic Futura.

<x-mail::table>
| | |
|:--|:--|
| Invoice | **{{ $payment->invoiceNumber() }}** |
| Program | {{ $session->program->name }} |
| Payment | {{ $payment->label }} |
| Due | {{ $payment->dueLabel() }} |
| Amount due | **{{ \App\Services\SessionPaymentPlan::rupiah($payment->amount) }}** |
| Total fee | {{ \App\Services\SessionPaymentPlan::rupiah($session->fee) }} |
</x-mail::table>

Please make the transfer and let us know once it is done. Contact us for our account details or any question: [admin@alfajarlogic.com](mailto:admin@alfajarlogic.com) or [WhatsApp](https://wa.me/6282125298452).
We will confirm your payment by email as soon as we have received it.

<x-mail::button :url="$loginUrl">
Open your portal
</x-mail::button>

Thank you,<br>
PT Alfajar Logic Futura
</x-mail::message>
