<?php

namespace App\Services;

use App\Http\Controllers\Training\TrainingSessionPaymentController;
use App\Models\Account;
use App\Models\Category;
use App\Models\Customer;
use App\Models\IncomeTransaction;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderFile;
use App\Models\ServiceOrderPayment;
use App\Models\ServiceOrderRevision;
use App\Models\ServicePackage;
use App\Models\Tax;
use App\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * The life of a service order (custom work such as an Excel dashboard):
 * brief → quoted → accepted → in progress → delivered → completed.
 * Money follows the company's usual rules: paying records income in Finance, cancelling a payment removes it again.
 */
class ServiceOrders
{
    public const REVENUE_CATEGORY = 'Service Revenue';

    /** Most files per upload. */
    public const MAX_FILES = 5;

    /** @return array<string, mixed> validation rules for the brief a customer fills in, in the portal or through the QR code */
    public static function briefRules(): array
    {
        return [
            'service_package_id' => ['nullable', Rule::exists(ServicePackage::class, 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string', 'max:3000'],
            'kpis' => ['nullable', 'string', 'max:3000'],
            'data_sources' => ['required', 'string', 'max:3000'],
            'data_volume' => ['nullable', 'string', 'max:255'],
            'excel_version' => ['nullable', 'string', 'max:100'],
            'needs_refresh' => ['nullable', 'boolean'],
            'deadline' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'addons' => ['nullable', 'array', 'max:20'],
            'addons.*' => ['string', 'max:120'],
            'files' => ['nullable', 'array', 'max:'.self::MAX_FILES],
            'files.*' => ['file', 'mimes:'.ServiceOrder::FILE_MIMES, 'max:'.ServiceOrder::FILE_MAX_KB],
        ];
    }

    /**
     * Saves a brief as a new order of the customer and tells the sales team.
     *
     * @param  array<string, mixed>  $data  validated brief
     */
    public static function createFromBrief(Customer $customer, array $data, bool $notify = true): ServiceOrder
    {
        $order = DB::transaction(function () use ($customer, $data) {
            $order = new ServiceOrder([
                ...collect($data)->except(['files', 'addons'])->all(),
                'needs_refresh' => (bool) ($data['needs_refresh'] ?? false),
                'addons' => self::chosenAddons($data['service_package_id'] ?? null, $data['addons'] ?? []),
            ]);
            $order->customer_id = $customer->id;
            $order->status = ServiceOrder::BRIEF;
            $order->save();

            foreach ($data['files'] ?? [] as $file) {
                self::storeFile($order, $file, ServiceOrderFile::BRIEF);
            }

            return $order;
        });

        if ($notify) {
            ServiceOrderNotifier::briefSubmitted($order);
        }

        return $order;
    }

    /**
     * The add-ons the customer ticked, with the price fixed as listed today.
     *
     * @param  list<string>  $labels
     * @return list<array{label: string, price: float}>|null
     */
    public static function chosenAddons(?int $packageId, array $labels): ?array
    {
        $package = $packageId ? ServicePackage::find($packageId) : null;
        $chosen = collect($package?->addons ?? [])->filter(fn ($addon) => in_array($addon['label'], $labels, true))->values()->all();

        return $chosen ?: null;
    }

    /**
     * What the quote form starts with: the package's starting price plus each add-on the customer ticked.
     *
     * @return list<array{label: string, amount: float}>
     */
    public static function suggestedQuoteItems(ServiceOrder $order): array
    {
        if ($order->quote_items) {
            return $order->quote_items;
        }

        $items = [];

        if ($order->package) {
            $items[] = ['label' => $order->package->name, 'amount' => (float) $order->package->starting_price];
        }

        foreach ($order->addons ?? [] as $addon) {
            $items[] = ['label' => $addon['label'], 'amount' => (float) $addon['price']];
        }

        return $items ?: [['label' => $order->title, 'amount' => 0.0]];
    }

    /**
     * Writes (or rewrites) the quote and moves the order to "quoted".
     *
     * @param  array<string, mixed>  $data  validated quote
     */
    public static function quote(ServiceOrder $order, array $data): void
    {
        if (! $order->canBeQuoted()) {
            throw new DomainException('This order can no longer be quoted.');
        }

        $items = collect($data['items'])->map(fn ($item) => ['label' => trim($item['label']), 'amount' => round((float) $item['amount'], 2)])->values();

        $order->forceFill([
            'status' => ServiceOrder::QUOTED,
            'quote_items' => $items->all(),
            'quote_total' => $items->sum('amount'),
            'dp_percentage' => (int) $data['dp_percentage'],
            'estimated_days' => $data['estimated_days'] ?? null,
            'included_revisions' => (int) $data['included_revisions'],
            'quote_valid_until' => $data['quote_valid_until'] ?? null,
            'quote_notes' => $data['quote_notes'] ?? null,
            'quoted_at' => now(),
        ])->save();
    }

    /** The customer accepts the quote: the invoices are created and the down payment is invoiced at once. */
    public static function accept(ServiceOrder $order): void
    {
        if (! $order->hasStatus(ServiceOrder::QUOTED)) {
            throw new DomainException('There is no open quote to accept.');
        }

        if ($order->quoteExpired()) {
            throw new DomainException('This quote has expired. Please ask us for a new one.');
        }

        DB::transaction(function () use ($order) {
            $total = (float) $order->quote_total;
            $dp = $order->dp_percentage >= 100 ? $total : round($total * $order->dp_percentage / 100, 2);

            $order->payments()->create([
                'kind' => ServiceOrderPayment::DOWN_PAYMENT,
                'label' => $order->dp_percentage >= 100 ? 'Full payment' : "Down payment ({$order->dp_percentage}%)",
                'amount' => $dp,
            ]);

            if ($order->dp_percentage < 100) {
                $order->payments()->create([
                    'kind' => ServiceOrderPayment::FINAL,
                    'label' => 'Final payment ('.(100 - $order->dp_percentage).'%)',
                    'amount' => round($total - $dp, 2),
                ]);
            }

            $order->forceFill(['status' => ServiceOrder::ACCEPTED, 'accepted_at' => now()])->save();
        });

        ServiceOrderNotifier::sendDueInvoices($order);
        ServiceOrderNotifier::quoteAccepted($order);
    }

    public static function decline(ServiceOrder $order, ?string $reason): void
    {
        if (! $order->hasStatus(ServiceOrder::QUOTED)) {
            throw new DomainException('There is no open quote to decline.');
        }

        $order->forceFill(['status' => ServiceOrder::DECLINED, 'decline_reason' => $reason])->save();
        ServiceOrderNotifier::endedByCustomer($order);
    }

    /** Ends an open order. Unpaid invoices are dropped; what was already paid stays on record. */
    public static function cancel(ServiceOrder $order, bool $byCustomer): void
    {
        if (! $order->isOpen()) {
            throw new DomainException('This order is already closed.');
        }

        DB::transaction(function () use ($order) {
            $order->payments()->whereNull('income_transaction_id')->delete();
            $order->forceFill(['status' => ServiceOrder::CANCELLED, 'cancelled_at' => now()])->save();
        });

        $byCustomer ? ServiceOrderNotifier::endedByCustomer($order) : ServiceOrderNotifier::cancelledByCompany($order);
    }

    /** Uploads a file to the order; returns the record. */
    public static function storeFile(ServiceOrder $order, UploadedFile $file, string $kind, ?string $note = null): ServiceOrderFile
    {
        return $order->files()->create([
            'kind' => $kind,
            'path' => ImageCompressor::store($file, "service-orders/{$order->id}", 'local'),
            'original_name' => $file->getClientOriginalName(),
            'note' => $note,
        ]);
    }

    public static function deleteFile(ServiceOrderFile $file): void
    {
        Storage::disk('local')->delete($file->path);
        $file->delete();
    }

    /** Hands the work over for review; the final invoice goes out and any open revisions count as answered. */
    public static function deliver(ServiceOrder $order): void
    {
        if (! $order->hasStatus(ServiceOrder::IN_PROGRESS, ServiceOrder::DELIVERED)) {
            throw new DomainException('Only work in progress can be delivered. Record the down payment first.');
        }

        if (! $order->files()->whereIn('kind', [ServiceOrderFile::DRAFT, ServiceOrderFile::FINAL])->exists()) {
            throw new DomainException('Upload the draft or final file before delivering.');
        }

        $order->revisions()->whereNull('resolved_at')->update(['resolved_at' => now()]);
        $order->forceFill(['status' => ServiceOrder::DELIVERED, 'delivered_at' => $order->delivered_at ?? now()])->save();

        $order->unsetRelation('payments');
        self::refreshStatus($order);

        ServiceOrderNotifier::sendDueInvoices($order);
        ServiceOrderNotifier::delivered($order);
    }

    /** The customer asks for changes to the delivered work. */
    public static function requestRevision(ServiceOrder $order, string $message): ServiceOrderRevision
    {
        if (! $order->acceptsRevisionRequests()) {
            throw new DomainException('Changes can only be requested while the work is waiting for your review.');
        }

        $order->unsetRelation('revisions');
        $order->load('revisions');

        $revision = $order->revisions()->create([
            'message' => $message,
            'is_extra' => $order->revisionsLeft() === 0,
        ]);

        $order->forceFill(['status' => ServiceOrder::IN_PROGRESS])->save();
        ServiceOrderNotifier::revisionRequested($revision);

        return $revision;
    }

    /** An extra charge (e.g. a revision beyond the quote), invoiced at once. */
    public static function addCharge(ServiceOrder $order, string $label, float $amount): ServiceOrderPayment
    {
        if (! $order->isWorkable()) {
            throw new DomainException('Extra charges can only be added once the work has started.');
        }

        $payment = $order->payments()->create(['kind' => ServiceOrderPayment::EXTRA, 'label' => $label, 'amount' => $amount]);

        $order->unsetRelation('payments');
        self::refreshStatus($order);
        ServiceOrderNotifier::sendDueInvoices($order);

        return $payment;
    }

    /**
     * Records a payment as income in Finance, then lets the order move on (work starts, or the order completes).
     *
     * @param  array{account_id?: int|null, paid_date: string, payment_method: string, proof_url?: string|null}  $data
     */
    public static function recordPayment(ServiceOrderPayment $payment, array $data, User $user, ?UploadedFile $proof): void
    {
        DB::transaction(function () use ($payment, $data, $user, $proof) {
            $payment->loadMissing('order.customer');
            $order = $payment->order;

            $category = Category::firstOrCreate(
                ['name' => self::REVENUE_CATEGORY, 'type' => 'income'],
                ['is_active' => true, 'description' => 'Customer payments for custom work such as Excel dashboards'],
            );

            $isTransfer = $data['payment_method'] === 'Bank Transfer';

            $account = $isTransfer
                ? Account::findOrFail($data['account_id'])
                : Account::firstOrCreate(
                    ['name' => TrainingSessionPaymentController::CASH_ACCOUNT_NAME],
                    ['account_type' => 'cash', 'is_active' => true, 'description' => 'Cash on hand'],
                );

            $income = IncomeTransaction::create([
                'transaction_number' => 'INC-'.date('YmdHis').'-'.rand(1000, 9999),
                'transaction_date' => $data['paid_date'],
                'account_id' => $account->id,
                'category_id' => $category->id,
                'source' => $order->customer->name,
                'description' => trim("{$order->title} — {$payment->label} ({$order->orderNumber()})".($order->customer->customer_code ? " (customer {$order->customer->customer_code})" : '')),
                ...TaxCalculator::apply($payment->amount, Tax::defaultForIncome()?->id),
                'payment_method' => $data['payment_method'],
                'created_by' => $user->id,
            ]);

            $payment->update([
                'paid_date' => $data['paid_date'],
                'income_transaction_id' => $income->id,
                'proof_path' => $isTransfer && $proof ? ImageCompressor::store($proof, "service-payments/{$payment->id}", 'local') : null,
                'proof_original_name' => $isTransfer && $proof ? $proof->getClientOriginalName() : null,
                'proof_url' => $isTransfer ? ($data['proof_url'] ?? null) : null,
            ]);

            $order->unsetRelation('payments');
            self::refreshStatus($order);
        });
    }

    /** Reverses a recorded payment and removes its income from Finance. */
    public static function cancelPayment(ServiceOrderPayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment->incomeTransaction?->delete();

            if ($payment->proof_path) {
                Storage::disk('local')->delete($payment->proof_path);
            }

            $payment->update(['paid_date' => null, 'income_transaction_id' => null, 'proof_path' => null, 'proof_original_name' => null, 'proof_url' => null]);

            $order = $payment->order;
            $order->unsetRelation('payments');
            self::refreshStatus($order);
        });
    }

    /**
     * Brings the status in line with the payments: work starts when the down payment is in, a delivered order
     * completes once everything is paid, and both step back if a payment is cancelled or a charge is added.
     */
    public static function refreshStatus(ServiceOrder $order): void
    {
        $order->load('payments');

        if ($order->hasStatus(ServiceOrder::ACCEPTED) && $order->downPaymentPaid()) {
            $order->forceFill(['status' => ServiceOrder::IN_PROGRESS, 'started_at' => now()])->save();
        } elseif ($order->hasStatus(ServiceOrder::IN_PROGRESS) && ! $order->downPaymentPaid() && $order->delivered_at === null) {
            $order->forceFill(['status' => ServiceOrder::ACCEPTED, 'started_at' => null])->save();
        }

        if ($order->hasStatus(ServiceOrder::DELIVERED) && $order->isFullyPaid()) {
            $order->forceFill(['status' => ServiceOrder::COMPLETED, 'completed_at' => now()])->save();
        } elseif ($order->hasStatus(ServiceOrder::COMPLETED) && ! $order->isFullyPaid()) {
            $order->forceFill(['status' => ServiceOrder::DELIVERED, 'completed_at' => null])->save();
        }
    }
}
