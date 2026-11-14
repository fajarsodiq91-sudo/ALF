<?php

namespace App\Services;

use App\Mail\ServiceOrderMail;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderPayment;
use App\Models\ServiceOrderRevision;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Emails about service orders. A failed send is logged and never blocks the action that triggered it. */
class ServiceOrderNotifier
{
    public static function briefSubmitted(ServiceOrder $order): void
    {
        $order->loadMissing('customer', 'package');

        StaffNotifier::serviceOrderActivity("New service request {$order->orderNumber()}", 'New service request', [
            "{$order->customer->name} sent a brief and is waiting for a quote.",
        ], [
            'Order' => $order->orderNumber(),
            'Request' => $order->title,
            'Package' => $order->package?->name ?? 'Custom / not sure yet',
            'Deadline' => $order->deadline?->format('d M Y') ?? '—',
        ], 'Prepare the quote', route('services.orders.show', $order));
    }

    /** Returns whether the email went out. */
    public static function quoteSent(ServiceOrder $order): bool
    {
        $order->loadMissing('customer');

        return self::toCustomer($order, new ServiceOrderMail(
            "Quote for {$order->title}",
            'Your quote is ready',
            [
                "Dear {$order->customer->name}, thank you for your request. We have prepared a quote for **{$order->title}**.",
                'Please review the scope and price in your customer portal, then accept or decline it there.',
            ],
            [
                'Order' => $order->orderNumber(),
                ...($order->hasDiscount() ? [
                    'Subtotal' => SessionPaymentPlan::rupiah($order->quoteSubtotal()),
                    'Discount'.($order->discount_label ? " ({$order->discount_label})" : '') => '- '.SessionPaymentPlan::rupiah($order->discount_amount),
                ] : []),
                'Total' => SessionPaymentPlan::rupiah($order->quote_total),
                'Down payment' => $order->dp_percentage.'%',
                'Estimated time' => $order->estimated_days ? $order->estimated_days.' working days after the down payment' : 'To be agreed',
                'Valid until' => $order->quote_valid_until?->format('d M Y') ?? '—',
            ],
            'View the quote',
            route('portal.orders.show', $order),
        ));
    }

    public static function quoteAccepted(ServiceOrder $order): void
    {
        $order->loadMissing('customer');

        StaffNotifier::serviceOrderActivity("Quote accepted {$order->orderNumber()}", 'Quote accepted', [
            "{$order->customer->name} accepted the quote. The down-payment invoice was sent; work starts once it is recorded as paid.",
        ], [
            'Order' => $order->orderNumber(),
            'Request' => $order->title,
            'Total' => SessionPaymentPlan::rupiah($order->quote_total),
        ], 'Open the order', route('services.orders.show', $order));
    }

    /** The customer ended the order (declined the quote or cancelled it). */
    public static function endedByCustomer(ServiceOrder $order): void
    {
        $order->loadMissing('customer');

        StaffNotifier::serviceOrderActivity("{$order->statusLabel()}: {$order->orderNumber()}", $order->statusLabel(), [
            "{$order->customer->name} ended the order {$order->orderNumber()} ({$order->title}).",
            ...($order->decline_reason ? ["Reason: {$order->decline_reason}"] : []),
        ], [], 'Open the order', route('services.orders.show', $order));
    }

    /** Invoices every payment of the order that is due, unpaid and not emailed yet; returns how many went out. */
    public static function sendDueInvoices(ServiceOrder $order): int
    {
        $order->unsetRelation('payments');
        $order->load('customer', 'payments');

        $sent = 0;

        foreach ($order->payments as $payment) {
            if ($payment->isPaid() || $payment->invoice_sent_at !== null || ! $payment->isDue()) {
                continue;
            }

            if (self::invoice($payment)) {
                $sent++;
            }
        }

        return $sent;
    }

    private static function invoice(ServiceOrderPayment $payment): bool
    {
        $order = $payment->order->loadMissing('customer');

        $sent = self::toCustomer($order, new ServiceOrderMail(
            "Invoice {$payment->invoiceNumber()}",
            'Invoice',
            [
                "Dear {$order->customer->name}, here is your invoice for **{$order->title}**.",
                $payment->kind === ServiceOrderPayment::FINAL
                    ? 'Once it is paid, the final files are released in your portal.'
                    : ($payment->kind === ServiceOrderPayment::DOWN_PAYMENT ? 'We start the work as soon as the down payment is confirmed.' : 'Please settle it at your earliest convenience.'),
                'Reply to this email with your transfer proof, or send it to us on WhatsApp.',
            ],
            [
                'Invoice' => $payment->invoiceNumber(),
                'Order' => $order->orderNumber(),
                'Payment' => $payment->label,
                'Amount' => SessionPaymentPlan::rupiah($payment->amount),
            ],
            'Open the order',
            route('portal.orders.show', $order),
            Setting::get('company_bank_account'),
        ));

        if ($sent) {
            $payment->forceFill(['invoice_sent_at' => now()])->save();
        }

        return $sent;
    }

    /** Returns whether the thank-you email went out. */
    public static function paymentReceived(ServiceOrderPayment $payment): bool
    {
        $order = $payment->order->loadMissing('customer', 'payments');

        $lines = ["Dear {$order->customer->name}, we have received and confirmed your payment. Thank you!"];

        if ($order->isFullyPaid() && $order->files()->where('kind', 'final')->exists()) {
            $lines[] = 'Your order is fully paid, so the final files are now available in your portal.';
        } elseif ($payment->kind === ServiceOrderPayment::DOWN_PAYMENT) {
            $lines[] = 'We are starting the work now. We will email you when the first version is ready.';
        }

        return self::toCustomer($order, new ServiceOrderMail(
            'Payment received',
            'Thank you for your payment',
            $lines,
            [
                'Invoice' => $payment->invoiceNumber(),
                'Payment' => $payment->label,
                'Amount received' => SessionPaymentPlan::rupiah($payment->amount),
                'Paid on' => $payment->paid_date?->format('d M Y') ?? '—',
            ],
            'Open the order',
            route('portal.orders.show', $order),
        ));
    }

    public static function delivered(ServiceOrder $order): bool
    {
        $order->loadMissing('customer');

        return self::toCustomer($order, new ServiceOrderMail(
            "First version ready: {$order->title}",
            'Your work is ready for review',
            [
                "Dear {$order->customer->name}, the work on **{$order->title}** is ready for your review in the portal.",
                "You may ask for changes (revisions included in your quote: {$order->included_revisions}). The final files are released once the final payment is confirmed.",
            ],
            ['Order' => $order->orderNumber()],
            'Review it',
            route('portal.orders.show', $order),
        ));
    }

    public static function revisionRequested(ServiceOrderRevision $revision): void
    {
        $order = $revision->order->loadMissing('customer');

        StaffNotifier::serviceOrderActivity("Revision requested {$order->orderNumber()}", 'Revision requested', [
            "{$order->customer->name} asked for changes to **{$order->title}**:",
            $revision->message,
            ...($revision->is_extra ? ['This is beyond the revisions included in the quote. You may add an extra charge.'] : []),
        ], ['Order' => $order->orderNumber()], 'Open the order', route('services.orders.show', $order));
    }

    /** The company ended the order: tell the customer. */
    public static function cancelledByCompany(ServiceOrder $order): bool
    {
        $order->loadMissing('customer');

        return self::toCustomer($order, new ServiceOrderMail(
            "Order cancelled: {$order->title}",
            'Your order was cancelled',
            ["Dear {$order->customer->name}, we had to cancel order {$order->orderNumber()} ({$order->title}). Please contact us if you have any questions."],
        ));
    }

    private static function toCustomer(ServiceOrder $order, ServiceOrderMail $mail): bool
    {
        if (! $order->customer?->email) {
            return false;
        }

        try {
            Mail::to($order->customer->email)->send($mail);

            return true;
        } catch (Throwable $exception) {
            Log::error('Could not send the service order email.', ['order_id' => $order->id, 'subject' => $mail->mailSubject, 'error' => $exception->getMessage()]);

            return false;
        }
    }
}
