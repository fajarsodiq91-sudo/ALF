<?php

namespace App\Mail;

use App\Models\Setting;
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
        $session = $this->payment->session->loadMissing(['program', 'customer', 'payments', 'meetings']);
        [$dueNow, $upcoming] = $session->payments->reject->isPaid()
            ->partition(fn (TrainingSessionPayment $payment) => $payment->setRelation('session', $session)->isDue());

        return new Content(markdown: 'emails.payment-received', with: [
            'session' => $session,
            'dueNow' => $dueNow,
            'upcoming' => $upcoming,
            'bankAccount' => Setting::get('company_bank_account'),
        ]);
    }
}
