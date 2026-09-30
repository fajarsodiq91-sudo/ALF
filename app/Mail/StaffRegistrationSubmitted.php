<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Tells the owner and staff that a customer submitted their registration and waits for approval. */
class StaffRegistrationSubmitted extends Mailable
{
    public function __construct(public Customer $customer) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "New registration waiting for approval: {$this->customer->name} | PT Alfajar Logic Futura");
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.staff-registration-submitted',
            with: [
                'requested' => $this->customer->requestedProgramSummaries(),
                'reviewUrl' => route('sales.review', $this->customer),
            ],
        );
    }
}
