<?php

namespace App\Services;

use App\Mail\PaymentInvoiceMail;
use App\Mail\PaymentReceivedMail;
use App\Models\TrainingSession;
use App\Models\TrainingSessionPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Emails the customer an invoice for each payment as it falls due, and a thank-you once it is confirmed. */
class PaymentInvoices
{
    /** Sends the invoices of a session that are due, unpaid and not yet emailed; returns how many went out. */
    public static function sendDue(TrainingSession $session): int
    {
        $session->unsetRelation('payments');
        $session->load(['customer', 'meetings', 'payments']);

        if (! $session->customer?->email) {
            return 0;
        }

        $sent = 0;

        foreach ($session->payments as $payment) {
            if ($payment->isPaid() || $payment->invoice_sent_at !== null || ! $payment->isDue()) {
                continue;
            }

            try {
                Mail::to($session->customer->email)->send(new PaymentInvoiceMail($payment));
                $payment->forceFill(['invoice_sent_at' => now()])->save();
                $sent++;
            } catch (Throwable $exception) {
                Log::error('Could not send the payment invoice.', ['payment_id' => $payment->id, 'error' => $exception->getMessage()]);
            }
        }

        return $sent;
    }

    /** For the daily schedule: every session that still has a due invoice waiting. */
    public static function sendAllDue(): int
    {
        return TrainingSession::query()
            ->whereNotNull('customer_id')
            ->where('status', '!=', 'cancelled')
            ->whereHas('payments', fn ($query) => $query->whereNull('income_transaction_id')->whereNull('invoice_sent_at'))
            ->get()
            ->sum(fn (TrainingSession $session) => self::sendDue($session));
    }

    /** Returns whether the thank-you email went out. */
    public static function sendThanks(TrainingSessionPayment $payment): bool
    {
        $customer = $payment->session->customer;

        if (! $customer?->email) {
            return false;
        }

        try {
            Mail::to($customer->email)->send(new PaymentReceivedMail($payment));

            return true;
        } catch (Throwable $exception) {
            Log::error('Could not send the payment thank-you email.', ['payment_id' => $payment->id, 'error' => $exception->getMessage()]);

            return false;
        }
    }
}
