<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\InviteCustomerRequest;
use App\Http\Requests\Sales\StoreCustomerRequest;
use App\Http\Requests\Sales\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\Project;
use App\Models\TrainingSession;
use App\Services\QrCodeGenerator;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::query()
            ->when($request->input('status') === 'awaiting', fn ($query) => $query->where('registration_status', Customer::REGISTRATION_AWAITING))
            ->when(in_array($request->input('status'), ['active', 'inactive'], true), fn ($query) => $query->where('is_active', $request->input('status') === 'active'))
            ->when($request->filled('type'), fn ($query) => $query->where('customer_type', $request->string('type')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(fn ($inner) => $inner->where('name', 'like', $term)
                    ->orWhere('customer_code', 'like', $term)
                    ->orWhere('contact_person', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->orderByRaw('name is null desc')
            ->orderBy('name')
            ->get();

        return view('erp.sales.customers.index', ['customers' => $customers]);
    }

    public function create(): View
    {
        $this->authorize('sales.manage');

        return view('erp.sales.customers.create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['photo']);

        try {
            $customer = new Customer([...$data, 'is_active' => $request->boolean('is_active')]);
            $customer->photo_path = $this->storePhoto($request->file('photo'));
            $customer->save();
        } catch (DomainException $exception) {
            if ($customer->photo_path) {
                Storage::disk('public')->delete($customer->photo_path);
            }

            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('sales.index')
            ->with('status', 'Customer created successfully.');
    }

    /** Admin picks only the type; the customer fills in the rest via a QR code / link. */
    public function invite(InviteCustomerRequest $request): RedirectResponse
    {
        $customer = new Customer(['customer_type' => $request->validated('customer_type'), 'is_active' => true]);
        $customer->registration_status = Customer::REGISTRATION_AWAITING;
        $customer->save();
        $customer->issueRegistrationToken();

        return redirect()->route('sales.invite.show', $customer);
    }

    public function showInvite(Customer $customer): View|RedirectResponse
    {
        $this->authorize('sales.manage');

        if (! $customer->isAwaitingCustomer()) {
            return redirect()->route('sales.index')->with('error', 'This customer has already completed their registration.');
        }

        $link = $customer->hasValidRegistrationToken()
            ? route('customer-registration.show', $customer->registration_token)
            : null;

        return view('erp.sales.customers.invite', [
            'customer' => $customer,
            'link' => $link,
            'qr' => $link ? QrCodeGenerator::svg($link) : null,
        ]);
    }

    public function regenerateInvite(Customer $customer): RedirectResponse
    {
        $this->authorize('sales.manage');

        if (! $customer->isAwaitingCustomer()) {
            return redirect()->route('sales.index')->with('error', 'This customer has already completed their registration.');
        }

        $customer->issueRegistrationToken();

        return redirect()->route('sales.invite.show', $customer)->with('status', 'A new link was generated. The previous QR code no longer works.');
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('sales.manage');

        return view('erp.sales.customers.edit', ['customer' => $customer]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $data = [...$request->safe()->except(['photo', 'remove_photo']), 'is_active' => $request->boolean('is_active')];
        $oldPhoto = $customer->photo_path;

        if ($request->hasFile('photo')) {
            $customer->photo_path = $this->storePhoto($request->file('photo'));
        } elseif ($request->boolean('remove_photo')) {
            $customer->photo_path = null;
        }

        try {
            // An admin filling in a pending customer completes the registration, same as the customer would.
            $customer->isAwaitingCustomer() ? $customer->completeRegistration($data) : $customer->update($data);
        } catch (DomainException $exception) {
            if ($request->hasFile('photo')) {
                Storage::disk('public')->delete($customer->photo_path);
            }

            return back()->withInput()->with('error', $exception->getMessage());
        }

        if ($oldPhoto && $oldPhoto !== $customer->photo_path) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return redirect()
            ->route('sales.index')
            ->with('status', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('sales.manage');

        if (TrainingSession::where('customer_id', $customer->id)->exists() || Project::where('customer_id', $customer->id)->exists()) {
            return redirect()->route('sales.index')
                ->with('error', 'This customer has training sessions or projects and cannot be deleted. Mark it inactive instead.');
        }

        $customer->delete();

        return redirect()
            ->route('sales.index')
            ->with('status', 'Customer deleted successfully.');
    }

    private function storePhoto(?UploadedFile $photo): ?string
    {
        return $photo?->store('customer-photos', 'public');
    }
}
