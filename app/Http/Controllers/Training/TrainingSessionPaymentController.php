<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Http\Requests\Training\PayTrainingSessionPaymentRequest;
use App\Models\Account;
use App\Models\Category;
use App\Models\IncomeTransaction;
use App\Models\TrainingSessionPayment;
use App\Services\ImageCompressor;
use App\Services\PaymentInvoices;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Records a customer's training payment as income in Finance, and reverses it. */
class TrainingSessionPaymentController extends Controller
{
    public const REVENUE_CATEGORY = 'Training Revenue';

    public const CASH_ACCOUNT_NAME = 'Cash';

    public function pay(PayTrainingSessionPaymentRequest $request, TrainingSessionPayment $payment): RedirectResponse
    {
        $back = redirect()->route('training.edit', $payment->training_session_id);

        if ($payment->isPaid()) {
            return $back->with('error', 'This payment is already recorded.');
        }

        $proofFile = $request->file('proof');

        DB::transaction(function () use ($request, $payment, $proofFile) {
            $payment->loadMissing('session.program', 'session.customer');
            $session = $payment->session;

            $category = Category::firstOrCreate(
                ['name' => self::REVENUE_CATEGORY, 'type' => 'income'],
                ['is_active' => true, 'description' => 'Customer payments for training and consulting programs'],
            );

            $isTransfer = $request->input('payment_method') === 'Bank Transfer';

            $account = $isTransfer
                ? Account::findOrFail($request->input('account_id'))
                : Account::firstOrCreate(
                    ['name' => self::CASH_ACCOUNT_NAME],
                    ['account_type' => 'cash', 'is_active' => true, 'description' => 'Cash on hand'],
                );

            $income = IncomeTransaction::create([
                'transaction_number' => 'INC-'.date('YmdHis').'-'.rand(1000, 9999),
                'transaction_date' => $request->input('paid_date'),
                'account_id' => $account->id,
                'category_id' => $category->id,
                'source' => $session->customer?->name ?? 'Public batch',
                'description' => trim("{$session->program->name} — {$payment->label}".($session->customer?->customer_code ? " (customer {$session->customer->customer_code})" : '')),
                'subtotal' => $payment->amount,
                'tax_amount' => 0,
                'amount' => $payment->amount,
                'payment_method' => $request->input('payment_method'),
                'created_by' => $request->user()->id,
            ]);

            $payment->update([
                'paid_date' => $request->input('paid_date'),
                'income_transaction_id' => $income->id,
                'proof_path' => $isTransfer && $proofFile ? ImageCompressor::store($proofFile, "training-payments/{$payment->id}", 'local') : null,
                'proof_original_name' => $isTransfer && $proofFile ? $proofFile->getClientOriginalName() : null,
                'proof_url' => $isTransfer ? $request->input('proof_url') : null,
            ]);
        });

        PaymentInvoices::sendThanks($payment->fresh('session.customer'));

        return $back->with('status', 'Payment recorded as income in Finance. A thank-you email was sent to the customer.');
    }

    public function cancel(TrainingSessionPayment $payment): RedirectResponse
    {
        $this->authorize('finance.manage');

        $back = redirect()->route('training.edit', $payment->training_session_id);

        if (! $payment->isPaid()) {
            return $back->with('error', 'This payment is not recorded.');
        }

        DB::transaction(function () use ($payment) {
            $payment->incomeTransaction?->delete();

            if ($payment->proof_path) {
                Storage::disk('local')->delete($payment->proof_path);
            }

            $payment->update([
                'paid_date' => null,
                'income_transaction_id' => null,
                'proof_path' => null,
                'proof_original_name' => null,
                'proof_url' => null,
            ]);
        });

        return $back->with('status', 'Payment cancelled and the Finance income removed.');
    }

    public function proof(TrainingSessionPayment $payment): StreamedResponse
    {
        $this->authorize('finance.manage');

        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->download($payment->proof_path, $payment->proof_original_name);
    }
}
