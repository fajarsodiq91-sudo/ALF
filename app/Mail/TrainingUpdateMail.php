<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** A short "something changed in your program" notice; the details are label => value rows. */
class TrainingUpdateMail extends Mailable
{
    /** @param  array<string, string>  $details */
    public function __construct(public Customer $recipient, public string $heading, public string $message, public array $details = []) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->heading.' | PT Alfajar Logic Futura');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.training-update', with: ['loginUrl' => route('portal.login')]);
    }
}
