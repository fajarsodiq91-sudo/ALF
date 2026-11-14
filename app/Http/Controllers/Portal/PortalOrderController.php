<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderFile;
use App\Models\ServicePackage;
use App\Services\ServiceOrders;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Where customers order custom work (e.g. an Excel dashboard), accept the quote, review the result and download it. */
class PortalOrderController extends Controller
{
    public function index(Request $request): View
    {
        $customer = $request->user('customer');

        return view('portal.orders.index', [
            'customer' => $customer,
            'orders' => $customer->serviceOrders()->with(['package', 'payments'])->latest('id')->get(),
            'canOrder' => ! $customer->isParticipant(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user('customer')->isParticipant()) {
            return redirect()->route('portal.orders.index')->with('error', 'Orders are placed by the customer who registered; please ask them.');
        }

        return view('portal.orders.create', [
            'packages' => ServicePackage::where('is_active', true)->orderBy('sort_order')->orderBy('starting_price')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = $request->user('customer');

        abort_if($customer->isParticipant(), 403);

        $order = ServiceOrders::createFromBrief($customer, $request->validate(ServiceOrders::briefRules()));

        return redirect()->route('portal.orders.show', $order)->with('status', 'Thank you! We received your brief and will send you a quote soon.');
    }

    public function show(Request $request, ServiceOrder $order): View
    {
        $this->authorizeOwner($request, $order);

        return view('portal.orders.show', ['order' => $order->load(['package', 'payments', 'files', 'revisions'])]);
    }

    public function accept(Request $request, ServiceOrder $order): RedirectResponse
    {
        $this->authorizeOwner($request, $order);

        try {
            ServiceOrders::accept($order);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Quote accepted. We emailed you the down-payment invoice; we start as soon as it is confirmed.');
    }

    public function decline(Request $request, ServiceOrder $order): RedirectResponse
    {
        $this->authorizeOwner($request, $order);

        $data = $request->validate(['decline_reason' => ['nullable', 'string', 'max:1000']]);

        try {
            ServiceOrders::decline($order, $data['decline_reason'] ?? null);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Quote declined. Thank you for letting us know.');
    }

    public function cancel(Request $request, ServiceOrder $order): RedirectResponse
    {
        $this->authorizeOwner($request, $order);

        // Once work has started the company has to agree, so the customer may only withdraw a request that is not agreed yet.
        if (! $order->hasStatus(ServiceOrder::BRIEF, ServiceOrder::QUOTED)) {
            return back()->with('error', 'Work on this order has already been agreed. Please contact us to cancel it.');
        }

        try {
            ServiceOrders::cancel($order, byCustomer: true);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Your request was cancelled.');
    }

    /** More sample data or references while the order is still open. */
    public function storeFile(Request $request, ServiceOrder $order): RedirectResponse
    {
        $this->authorizeOwner($request, $order);

        abort_unless($order->isOpen(), 403);

        $data = $request->validate([
            'files' => ['required', 'array', 'max:'.ServiceOrders::MAX_FILES],
            'files.*' => ['file', 'mimes:'.ServiceOrder::FILE_MIMES, 'max:'.ServiceOrder::FILE_MAX_KB],
        ]);

        foreach ($data['files'] as $file) {
            ServiceOrders::storeFile($order, $file, ServiceOrderFile::BRIEF);
        }

        return back()->with('status', 'Files uploaded.');
    }

    public function requestRevision(Request $request, ServiceOrder $order): RedirectResponse
    {
        $this->authorizeOwner($request, $order);

        $data = $request->validate(['message' => ['required', 'string', 'max:3000']]);

        try {
            ServiceOrders::requestRevision($order, $data['message']);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Your change request was sent. We will email you when the new version is ready.');
    }

    public function downloadFile(Request $request, ServiceOrderFile $file): StreamedResponse|RedirectResponse
    {
        $order = $file->order;
        $this->authorizeOwner($request, $order);

        if (! $file->releasedToCustomer()) {
            return redirect()->route('portal.orders.show', $order)->with('error', 'The final files are released once every invoice of this order is paid.');
        }

        abort_unless(Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->download($file->path, $file->original_name);
    }

    private function authorizeOwner(Request $request, ServiceOrder $order): void
    {
        abort_unless($order->customer_id === $request->user('customer')->id, 404);
    }
}
