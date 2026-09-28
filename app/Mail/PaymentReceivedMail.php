<?php

namespace App\Mail;

use App\Models\TrainingSessionPayment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PaymentReceivedMail extends Mailable
{
    public function __construct(public TrainingSessionPayment $payment) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Thank you for your payment | PT Alfajar Logic Futura');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.payment-received', with: [
            'session' => $this->payment->session->loadMissing(['program', 'customer', 'payments']),
        ]);
    }
}
