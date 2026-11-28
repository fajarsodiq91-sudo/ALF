<?php

namespace App\Services;

use App\Mail\PaymentInvoiceMail;
use App\Mail\PaymentReceivedMail;
use App\Models\TrainingSession;
use App\Models\TrainingSessionPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Emails the customer an invoice for the payments that fall due, and a thank-you once it is confirmed. */
class PaymentInvoices
{
    /** Emails every payment of a session that is due, unpaid and not yet invoiced, together in one email; returns how many payments were invoiced. */
    public static function sendDue(TrainingSession $session): int
    {
        $session->unsetRelation('payments');
        $session->load(['customer', 'meetings', 'payments']);

        if (! $session->customer?->email) {
            return 0;
        }

        $due = $session->payments
            ->filter(fn (TrainingSessionPayment $payment) => ! $payment->isPaid() && $payment->invoice_sent_at === null && $payment->isDue())
            ->values();

        if ($due->isEmpty()) {
            return 0;
        }

        try {
            Mail::to($session->customer->email)->send(new PaymentInvoiceMail($due));
        } catch (Throwable $exception) {
            Log::error('Could not send the payment invoice.', ['payment_ids' => $due->modelKeys(), 'error' => $exception->getMessage()]);

            return 0;
        }

        $due->each(fn (TrainingSessionPayment $payment) => $payment->forceFill(['invoice_sent_at' => now()])->save());

        return $due->count();
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
