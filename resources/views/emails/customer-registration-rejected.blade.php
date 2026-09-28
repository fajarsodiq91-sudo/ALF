<x-mail::message>
# Hello, {{ $customer->name }}

Thank you for registering with PT Alfajar Logic Futura.

After reviewing your registration, we are **unable to approve it** at this time.

@if ($customer->rejection_reason)
**Reason:** {{ $customer->rejection_reason }}

@endif
If you believe this is a mistake, or you would like to register again, please contact us by replying to this email.

Regards,<br>
PT Alfajar Logic Futura
</x-mail::message>
