<?php

namespace App\Mail;

use App\Models\Setting;
use App\Models\TrainingSessionPayment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** One email invoicing one or more payments of the same session, such as the registration fee and the down payment. */
class PaymentInvoiceMail extends Mailable
{
    /** @param  Collection<int, TrainingSessionPayment>  $payments */
    public function __construct(public Collection $payments) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Invoice {$this->invoiceNumbers()} | PT Alfajar Logic Futura");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.payment-invoice', with: [
            'session' => $this->payments->first()->session->loadMissing(['program', 'customer']),
            'invoiceNumbers' => $this->invoiceNumbers(),
            'total' => $this->payments->sum('amount'),
            'bankAccount' => Setting::get('company_bank_account'),
            'loginUrl' => route('portal.login'),
        ]);
    }

    private function invoiceNumbers(): string
    {
        return $this->payments->map->invoiceNumber()->implode(', ');
    }
}
