<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Services\QuoteServiceOrderRequest;
use App\Models\Account;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderFile;
use App\Models\ServiceOrderRevision;
use App\Services\ServiceOrderNotifier;
use App\Services\ServiceOrders;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Where the sales team handles custom-work orders: quote, follow the work, hand it over. */
class ServiceOrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = ServiceOrder::query()
            ->with(['customer', 'package'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(fn ($inner) => $inner->where('title', 'like', $term)
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', $term)->orWhere('customer_code', 'like', $term)));
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('erp.services.orders.index', ['orders' => $orders]);
    }

    public function show(ServiceOrder $order): View
    {
        $order->load(['customer', 'package', 'payments.incomeTransaction', 'files', 'revisions']);

        return view('erp.services.orders.show', [
            'order' => $order,
            'suggestedItems' => ServiceOrders::suggestedQuoteItems($order),
            'accounts' => Account::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function quote(QuoteServiceOrderRequest $request, ServiceOrder $order): RedirectResponse
    {
        try {
            ServiceOrders::quote($order, $request->validated());
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        if (! ServiceOrderNotifier::quoteSent($order->fresh('customer'))) {
            return back()->with('error', 'The quote was saved, but the email to the customer could NOT be sent (no email address, or the mail settings need checking). The quote is visible in their portal.');
        }

        return back()->with('status', 'Quote sent. The customer can accept it in their portal.');
    }

    public function deliver(ServiceOrder $order): RedirectResponse
    {
        $this->authorize('sales.manage');

        try {
            ServiceOrders::deliver($order);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Delivered. The customer was emailed and the final invoice was sent.');
    }

    public function cancel(ServiceOrder $order): RedirectResponse
    {
        $this->authorize('sales.manage');

        try {
            ServiceOrders::cancel($order, byCustomer: false);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Order cancelled. Unpaid invoices were removed; payments already recorded stay in Finance.');
    }

    public function storeFile(Request $request, ServiceOrder $order): RedirectResponse
    {
        $this->authorize('sales.manage');

        $data = $request->validate([
            'kind' => ['required', Rule::in([ServiceOrderFile::DRAFT, ServiceOrderFile::FINAL])],
            'note' => ['nullable', 'string', 'max:255'],
            'files' => ['required', 'array', 'max:'.ServiceOrders::MAX_FILES],
            'files.*' => ['file', 'mimes:'.ServiceOrder::FILE_MIMES, 'max:'.ServiceOrder::FILE_MAX_KB],
        ]);

        foreach ($data['files'] as $file) {
            ServiceOrders::storeFile($order, $file, $data['kind'], $data['note'] ?? null);
        }

        return back()->with('status', 'File uploaded.');
    }

    public function destroyFile(ServiceOrderFile $file): RedirectResponse
    {
        $this->authorize('sales.manage');

        abort_if($file->kind === ServiceOrderFile::BRIEF, 403);

        ServiceOrders::deleteFile($file);

        return back()->with('status', 'File deleted.');
    }

    public function downloadFile(ServiceOrderFile $file): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->download($file->path, $file->original_name);
    }

    public function resolveRevision(ServiceOrderRevision $revision): RedirectResponse
    {
        $this->authorize('sales.manage');

        $revision->update(['resolved_at' => $revision->resolved_at ?? now()]);

        return back()->with('status', 'Revision marked as done.');
    }

    public function storeCharge(Request $request, ServiceOrder $order): RedirectResponse
    {
        $this->authorize('sales.manage');

        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1', 'max:9999999999'],
        ]);

        try {
            ServiceOrders::addCharge($order, $data['label'], (float) $data['amount']);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Extra charge added and invoiced.');
    }
}
