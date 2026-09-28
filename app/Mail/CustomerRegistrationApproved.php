<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CustomerRegistrationApproved extends Mailable
{
    public function __construct(public Customer $customer) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your registration is approved | PT Alfajar Logic Futura');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.customer-registration-approved',
            with: [
                'sessions' => $this->customer->sessions()->with(['program', 'meetings'])->orderBy('start_date')->get(),
                'loginUrl' => route('portal.login'),
            ],
        );
    }
}
