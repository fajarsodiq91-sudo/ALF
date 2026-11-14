<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** One template for every service-order email, to the customer or to staff: a heading, a few lines, optional details and a button. */
class ServiceOrderMail extends Mailable
{
    /**
     * @param  list<string>  $lines  paragraphs under the heading
     * @param  array<string, string>  $details  label => value rows shown as a table
     */
    public function __construct(
        public string $mailSubject,
        public string $heading,
        public array $lines = [],
        public array $details = [],
        public ?string $buttonLabel = null,
        public ?string $buttonUrl = null,
        public ?string $bankAccount = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "{$this->mailSubject} | PT Alfajar Logic Futura");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.service-order');
    }
}
