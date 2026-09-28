<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CustomerRegistrationRejected extends Mailable
{
    public function __construct(public Customer $customer) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'About your registration | PT Alfajar Logic Futura');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.customer-registration-rejected');
    }
}
