<?php

namespace App\Mail;

use App\Models\TrainingSessionPayment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PaymentInvoiceMail extends Mailable
{
    public function __construct(public TrainingSessionPayment $payment) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Invoice {$this->payment->invoiceNumber()} | PT Alfajar Logic Futura");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.payment-invoice', with: [
            'session' => $this->payment->session->loadMissing(['program', 'customer']),
            'bankAccount' => \App\Models\Setting::get('company_bank_account'),
            'loginUrl' => route('portal.login'),
        ]);
    }
}
