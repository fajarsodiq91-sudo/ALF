<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Training\PayTrainingSessionPaymentRequest;
use App\Models\ServiceOrderPayment;
use App\Services\ServiceOrderNotifier;
use App\Services\ServiceOrders;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Records a customer's payment for custom work as income in Finance, and reverses it. */
class ServiceOrderPaymentController extends Controller
{
    public function pay(PayTrainingSessionPaymentRequest $request, ServiceOrderPayment $payment): RedirectResponse
    {
        $back = redirect()->route('services.orders.show', $payment->service_order_id);

        if ($payment->isPaid()) {
            return $back->with('error', 'This payment is already recorded.');
        }

        if (! $payment->order->isOpen()) {
            return $back->with('error', 'This order is closed.');
        }

        ServiceOrders::recordPayment($payment, $request->safe()->only(['account_id', 'paid_date', 'payment_method', 'proof_url']), $request->user(), $request->file('proof'));

        if (! ServiceOrderNotifier::paymentReceived($payment->fresh('order.customer'))) {
            return $back->with('error', 'Payment recorded as income in Finance, but the thank-you email to the customer could NOT be sent (no email address on file, or the mail settings need checking).');
        }

        return $back->with('status', 'Payment recorded as income in Finance. A thank-you email was sent to the customer.');
    }

    public function cancel(ServiceOrderPayment $payment): RedirectResponse
    {
        $this->authorize('finance.manage');

        $back = redirect()->route('services.orders.show', $payment->service_order_id);

        if (! $payment->isPaid()) {
            return $back->with('error', 'This payment is not recorded.');
        }

        ServiceOrders::cancelPayment($payment);

        return $back->with('status', 'Payment cancelled and the Finance income removed.');
    }

    public function proof(ServiceOrderPayment $payment): StreamedResponse
    {
        $this->authorize('finance.manage');

        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->download($payment->proof_path, $payment->proof_original_name);
    }
}
