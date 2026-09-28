<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CustomerRegistrationReceived extends Mailable
{
    public function __construct(public Customer $customer) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'We received your registration | PT Alfajar Logic Futura');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.customer-registration-received');
    }
}
