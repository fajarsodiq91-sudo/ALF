@php
    $rupiah = fn ($amount) => \App\Services\SessionPaymentPlan::rupiah($amount);
    $input = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm';
    $primary = 'inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md transition-all duration-150';
    $secondary = 'inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50';
    $quoteItems = old('items', $suggestedItems);
@endphp
<x-layouts.erp :title="'Order '.$order->orderNumber()">
    <div class="max-w-5xl space-y-5">
        <x-erp.flash />

        @if ($errors->any())
            <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                <p class="font-medium">Please fix the following:</p>
                <ul class="mt-1 list-disc pl-5">@foreach (collect($errors->all())->unique() as $message)<li>{{ $message }}</li>@endforeach</ul>
            </div>
        @endif

        {{-- Header --}}
        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">{{ $order->title }}</h2>
                    <p class="text-sm text-gray-500">
                        {{ $order->orderNumber() }} · {{ $order->package?->name ?? 'Custom / not sure yet' }} · sent {{ $order->created_at->format('d M Y, H:i') }}
                    </p>
                    <p class="mt-1 text-sm text-gray-700">
                        <a href="{{ route('sales.show', $order->customer) }}" class="font-medium text-brand hover:text-brand-dark">{{ $order->customer->name }}</a>
                        @if ($order->customer->customer_code)<span class="font-mono text-xs text-gray-400">{{ $order->customer->customer_code }}</span>@endif
                        · {{ $order->customer->email ?: 'no email' }} · {{ $order->customer->phone ?: 'no phone' }}
                    </p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-medium {{ $order->statusStyle() }}">{{ $order->statusLabel() }}</span>
            </div>

            @if ($order->customer->isPendingApproval())
                <p class="mt-4 rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-800">
                    This customer's registration is still waiting for approval.
                    <a href="{{ route('sales.review', $order->customer) }}" class="font-medium underline">Review and approve it</a> first; then prepare the quote.
                </p>
            @endif
            @if ($order->hasStatus('declined') && $order->decline_reason)
                <p class="mt-4 rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-600">Declined by the customer: {{ $order->decline_reason }}</p>
            @endif
        </div>

        {{-- Brief --}}
        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-800">Brief</h3>
            <dl class="mt-3 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                @foreach (['purpose' => 'Purpose and users', 'kpis' => 'KPIs / questions', 'data_sources' => 'Data sources', 'notes' => 'Other notes'] as $field => $label)
                    <div class="sm:col-span-2"><dt class="text-xs text-gray-500">{{ $label }}</dt><dd class="whitespace-pre-line text-gray-800">{{ $order->{$field} ?: '—' }}</dd></div>
                @endforeach
                <div><dt class="text-xs text-gray-500">Data volume</dt><dd class="text-gray-800">{{ $order->data_volume ?: '—' }}</dd></div>
                <div><dt class="text-xs text-gray-500">Excel version</dt><dd class="text-gray-800">{{ $order->excel_version ?: '—' }}</dd></div>
                <div><dt class="text-xs text-gray-500">Deadline</dt><dd class="text-gray-800">{{ $order->deadline?->format('d M Y') ?? '—' }}</dd></div>
                <div><dt class="text-xs text-gray-500">Turnaround chosen</dt><dd class="text-gray-800">{{ $order->timeline_days ? $order->timeline_days.' working days ('.($order->timeline_price > 0 ? '+ '.$rupiah($order->timeline_price) : ($order->timeline_price < 0 ? '- '.$rupiah(-$order->timeline_price) : 'standard price')).')' : 'No preference' }}</dd></div>
                <div><dt class="text-xs text-gray-500">Data refresh</dt><dd class="text-gray-800">{{ $order->needs_refresh ? 'Needed regularly' : 'One-time data' }}</dd></div>
                @if ($order->addons)
                    <div class="sm:col-span-2"><dt class="text-xs text-gray-500">Add-ons ticked</dt><dd class="text-gray-800">{{ collect($order->addons)->map(fn ($addon) => $addon['label'].' ('.$rupiah($addon['price']).')')->implode(', ') }}</dd></div>
                @endif
            </dl>
            @php $briefFiles = $order->files->where('kind', 'brief'); @endphp
            @if ($briefFiles->isNotEmpty())
                <p class="mt-4 text-xs text-gray-500">Files from the customer</p>
                <ul class="mt-1 space-y-1 text-sm">
                    @foreach ($briefFiles as $file)
                        <li><a href="{{ route('services.files.download', $file) }}" class="text-brand hover:text-brand-dark">{{ $file->original_name }}</a></li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Quote --}}
        @if ($order->canBeQuoted() && auth()->user()->can('sales.manage'))
            <form action="{{ route('services.orders.quote', $order) }}" method="POST" class="bg-white rounded-lg shadow-md border border-gray-200 p-6"
                  x-data='{ items: @json(array_values($quoteItems)), dp: {{ (int) old("dp_percentage", $order->dp_percentage) }},
                            discountType: @json(old("discount_type", $order->discount_type ?? "amount")), discountValue: @json(old("discount_value", $order->discount_value ? (float) $order->discount_value : "")),
                            get subtotal() { return this.items.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0); },
                            get discount() { const v = parseFloat(this.discountValue) || 0; if (v <= 0) { return 0; } return Math.min(this.discountType === "percent" ? this.subtotal * Math.min(v, 100) / 100 : v, this.subtotal); },
                            get total() { return this.subtotal - this.discount; },
                            rupiah(n) { return "Rp " + new Intl.NumberFormat("id-ID").format(Math.round(n)); } }'>
                @csrf
                <h3 class="text-sm font-semibold text-gray-800">{{ $order->hasStatus('quoted') ? 'Quote (sent: you can still revise it until the customer accepts)' : 'Prepare the quote' }}</h3>
                <p class="mt-1 text-xs text-gray-500">Starts from the package price and the add-ons the customer ticked. Adjust lines for the real scope. The customer is emailed and accepts it in their portal.</p>

                <div class="mt-4 space-y-2">
                    <template x-for="(item, i) in items" :key="i">
                        <div class="flex items-center gap-2">
                            <input type="text" :name="`items[${i}][label]`" x-model="item.label" required maxlength="255" placeholder="Scope item" class="{{ $input }}">
                            <input type="number" :name="`items[${i}][amount]`" x-model="item.amount" step="1" required placeholder="Rp" class="w-40 shrink-0 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                            <button type="button" x-show="items.length > 1" @click="items.splice(i, 1)" class="text-sm text-gray-400 hover:text-red-600">Remove</button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="items.push({ label: '', amount: '' })" class="mt-2 text-sm font-medium text-brand hover:text-brand-dark">+ Add line</button>

                <p class="mt-1 text-xs text-gray-500">Use a negative amount for a reduction, for example a longer turnaround that costs less.</p>

                <div class="mt-4 flex flex-wrap items-end gap-3 rounded-md bg-gray-50 p-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Discount (leave the amount empty for none)</label>
                        <div class="mt-1 flex gap-2">
                            <select name="discount_type" x-model="discountType" class="rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                <option value="amount">Rp</option>
                                <option value="percent">%</option>
                            </select>
                            <input type="number" name="discount_value" x-model="discountValue" min="0" step="any" placeholder="No discount" class="w-36 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                        </div>
                    </div>
                    <div class="flex-1 min-w-[10rem]">
                        <label class="block text-xs font-medium text-gray-600">Discount label (optional)</label>
                        <input type="text" name="discount_label" maxlength="100" value="{{ old('discount_label', $order->discount_label) }}" placeholder="e.g. Early-bird, returning customer" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                    </div>
                </div>

                <div class="mt-3 space-y-0.5 text-right text-sm">
                    <p class="text-gray-600" x-show="discount > 0" x-cloak>Subtotal <span x-text="rupiah(subtotal)"></span></p>
                    <p class="text-green-700" x-show="discount > 0" x-cloak>Discount - <span x-text="rupiah(discount)"></span></p>
                    <p class="font-semibold text-gray-800">Total <span x-text="rupiah(total)"></span></p>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Down payment %</label>
                        <input type="number" name="dp_percentage" x-model.number="dp" min="10" max="100" required class="{{ $input }}">
                        <p class="mt-1 text-xs text-gray-500" x-text="dp >= 100 ? 'Paid in full upfront' : rupiah(total * dp / 100) + ' now, the rest on delivery'"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Estimated working days</label>
                        <input type="number" name="estimated_days" min="1" max="365" value="{{ old('estimated_days', $order->estimated_days ?? $order->timeline_days ?? $order->package?->estimated_days) }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Revisions included</label>
                        <input type="number" name="included_revisions" min="0" max="20" value="{{ old('included_revisions', $order->quoted_at ? $order->included_revisions : ($order->package?->included_revisions ?? 2)) }}" required class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Quote valid until</label>
                        <input type="date" name="quote_valid_until" min="{{ today()->toDateString() }}" value="{{ old('quote_valid_until', $order->quote_valid_until?->toDateString() ?? today()->addDays(14)->toDateString()) }}" class="{{ $input }}">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-xs font-medium text-gray-600">Notes to the customer (scope, assumptions, what is not included)</label>
                    <textarea name="quote_notes" rows="3" maxlength="3000" class="{{ $input }}">{{ old('quote_notes', $order->quote_notes) }}</textarea>
                </div>
                <div class="mt-4"><button type="submit" class="{{ $primary }}">{{ $order->hasStatus('quoted') ? 'Update & resend quote' : 'Send quote' }}</button></div>
            </form>
        @elseif ($order->quote_total)
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-800">Quote</h3>
                <table class="mt-3 w-full text-sm">
                    @foreach ($order->quote_items as $item)
                        <tr class="border-b border-gray-100"><td class="py-1.5 text-gray-700">{{ $item['label'] }}</td><td class="py-1.5 text-right text-gray-800">{{ $rupiah($item['amount']) }}</td></tr>
                    @endforeach
                    @if ($order->hasDiscount())
                        <tr><td class="pt-2 text-gray-600">Subtotal</td><td class="pt-2 text-right text-gray-600">{{ $rupiah($order->quoteSubtotal()) }}</td></tr>
                        <tr><td class="text-green-700">Discount @if ($order->discount_label)({{ $order->discount_label }}) @endif· {{ $order->discountDescription() }}</td><td class="text-right text-green-700">- {{ $rupiah($order->discount_amount) }}</td></tr>
                    @endif
                    <tr><td class="pt-2 font-semibold text-gray-800">Total</td><td class="pt-2 text-right font-semibold">{{ $rupiah($order->quote_total) }}</td></tr>
                </table>
                <p class="mt-2 text-xs text-gray-500">{{ $order->dp_percentage }}% down payment · {{ $order->included_revisions }} revision(s) · {{ $order->estimated_days ? $order->estimated_days.' working days' : 'no time estimate' }}@if ($order->accepted_at) · accepted {{ $order->accepted_at->format('d M Y') }}@endif</p>
            </div>
        @endif

        {{-- Payments --}}
        @if ($order->payments->isNotEmpty())
            <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-x-auto">
                <div class="px-6 pt-5"><h3 class="text-sm font-semibold text-gray-800">Payments</h3>
                    <p class="text-xs text-gray-500">Work starts when the down payment is recorded. The final files are released to the customer once every invoice is paid.</p></div>
                <table class="mt-3 min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-500">Invoice</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500">Payment</th>
                        <th class="px-4 py-2 text-right font-medium text-gray-500">Amount</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500">Status</th>
                        <th class="px-4 py-2 text-right font-medium text-gray-500"></th>
                    </tr></thead>
                    @foreach ($order->payments as $payment)
                        <tbody x-data="{ paying: false, method: 'Bank Transfer' }" class="divide-y divide-gray-100">
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs text-gray-600">{{ $payment->invoiceNumber() }}<span class="block font-sans text-gray-400">{{ $payment->invoice_sent_at ? 'emailed '.$payment->invoice_sent_at->format('d M') : 'not emailed yet' }}</span></td>
                                <td class="px-4 py-2 text-gray-800">{{ $payment->label }}<span class="block text-xs text-gray-400">Due: {{ strtolower($payment->dueLabel()) }}</span></td>
                                <td class="px-4 py-2 text-right text-gray-800">{{ $rupiah($payment->amount) }}</td>
                                <td class="px-4 py-2">
                                    @if ($payment->isPaid())
                                        <span class="rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Paid {{ $payment->paid_date?->format('d M Y') }}</span>
                                    @elseif ($payment->isDue())
                                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Due</span>
                                    @else
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">Upcoming</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-right whitespace-nowrap">
                                    @can('finance.manage')
                                        @if ($payment->isPaid())
                                            @if ($payment->proof_path)<a href="{{ route('services.payments.proof', $payment) }}" class="mr-2 text-xs text-brand hover:text-brand-dark">Proof</a>@elseif ($payment->proof_url)<a href="{{ $payment->proof_url }}" target="_blank" rel="noopener noreferrer" class="mr-2 text-xs text-brand hover:text-brand-dark">Proof</a>@endif
                                            <form action="{{ route('services.payments.cancel', $payment) }}" method="POST" class="inline" onsubmit="return confirm('Cancel this payment? The Finance income is removed.');">
                                                @csrf
                                                <button type="submit" class="text-xs text-gray-400 hover:text-red-600">Cancel payment</button>
                                            </form>
                                        @elseif ($order->isOpen())
                                            <button type="button" @click="paying = !paying" class="text-sm font-medium text-brand hover:text-brand-dark">Record payment</button>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                            @if (! $payment->isPaid())
                                @can('finance.manage')
                                    <tr x-show="paying" x-cloak class="bg-gray-50">
                                        <td colspan="5" class="px-4 py-3">
                                            <form action="{{ route('services.payments.pay', $payment) }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
                                                @csrf
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600">Method</label>
                                                    <select name="payment_method" x-model="method" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                                        @foreach (\App\Models\Payroll::PAYMENT_METHODS as $paymentMethod)<option value="{{ $paymentMethod }}">{{ $paymentMethod }}</option>@endforeach
                                                    </select>
                                                </div>
                                                <div x-show="method === 'Bank Transfer'">
                                                    <label class="block text-xs font-medium text-gray-600">Received in account</label>
                                                    <select name="account_id" :required="method === 'Bank Transfer'" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                                        @foreach ($accounts as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600">Date received</label>
                                                    <input type="date" name="paid_date" value="{{ now()->format('Y-m-d') }}" required class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                                </div>
                                                <div x-show="method === 'Bank Transfer'">
                                                    <label class="block text-xs font-medium text-gray-600">Proof of transfer (file)</label>
                                                    <input type="file" name="proof" accept=".pdf,.png,.jpg,.jpeg,.webp" class="mt-1 block text-xs text-gray-600 file:mr-2 file:rounded-md file:border-0 file:bg-gray-100 file:px-2 file:py-1.5 file:text-xs file:font-medium file:text-gray-700">
                                                </div>
                                                <div x-show="method === 'Bank Transfer'">
                                                    <label class="block text-xs font-medium text-gray-600">...or a link to the proof</label>
                                                    <input type="url" name="proof_url" placeholder="https://" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                                </div>
                                                <button type="submit" class="{{ $primary }}">Confirm &amp; add to Finance income</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endcan
                            @endif
                        </tbody>
                    @endforeach
                </table>
                <p class="px-6 py-3 text-xs text-gray-500">Paid {{ $rupiah($order->totalPaid()) }} of {{ $rupiah($order->totalCharged()) }}.</p>
            </div>
        @endif

        {{-- Work, files and revisions --}}
        @if ($order->isWorkable() || $order->files->whereIn('kind', ['draft', 'final'])->isNotEmpty())
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-800">Work and files</h3>

                @if ($order->revisions->isNotEmpty())
                    <div class="mt-3 space-y-2">
                        @foreach ($order->revisions as $revision)
                            <div class="rounded-md {{ $revision->isResolved() ? 'bg-gray-50' : 'bg-amber-50' }} px-3 py-2 text-sm">
                                <p class="whitespace-pre-line text-gray-800">{{ $revision->message }}</p>
                                <p class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                    {{ $revision->created_at->format('d M Y') }} · {{ $revision->isResolved() ? 'done' : 'open' }}
                                    @if ($revision->is_extra)<span class="rounded-full bg-red-50 px-2 py-0.5 text-red-700">beyond included revisions: add an extra charge below</span>@endif
                                    @if (! $revision->isResolved() && auth()->user()->can('sales.manage'))
                                        <form action="{{ route('services.revisions.resolve', $revision) }}" method="POST" class="inline">@csrf<button type="submit" class="text-brand hover:text-brand-dark">Mark done</button></form>
                                    @endif
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif

                @php $workFiles = $order->files->whereIn('kind', ['draft', 'final']); @endphp
                @if ($workFiles->isNotEmpty())
                    <ul class="mt-3 divide-y divide-gray-100 text-sm">
                        @foreach ($workFiles as $file)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                                <div>
                                    <a href="{{ route('services.files.download', $file) }}" class="text-brand hover:text-brand-dark">{{ $file->original_name }}</a>
                                    <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ \App\Models\ServiceOrderFile::KINDS[$file->kind] }}</span>
                                    @if ($file->note)<span class="block text-xs text-gray-500">{{ $file->note }}</span>@endif
                                </div>
                                @can('sales.manage')
                                    <form action="{{ route('services.files.destroy', $file) }}" method="POST" onsubmit="return confirm('Delete this file?');">@csrf @method('DELETE')<button type="submit" class="text-xs text-gray-400 hover:text-red-600">Delete</button></form>
                                @endcan
                            </li>
                        @endforeach
                    </ul>
                @endif

                @can('sales.manage')
                    @if ($order->isOpen() && $order->isWorkable())
                        <form action="{{ route('services.orders.files.store', $order) }}" method="POST" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-end gap-3 rounded-md bg-gray-50 p-3">
                            @csrf
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Type</label>
                                <select name="kind" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                                    <option value="draft">Draft (customer can always download)</option>
                                    <option value="final">Final (released after full payment)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Note (optional)</label>
                                <input type="text" name="note" maxlength="255" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">File(s)</label>
                                <input type="file" name="files[]" multiple required class="mt-1 block text-xs text-gray-600 file:mr-2 file:rounded-md file:border-0 file:bg-gray-100 file:px-2 file:py-1.5 file:text-xs file:font-medium file:text-gray-700">
                            </div>
                            <button type="submit" class="{{ $secondary }}">Upload</button>
                        </form>

                        @if ($order->hasStatus('in_progress', 'delivered'))
                            <form action="{{ route('services.orders.deliver', $order) }}" method="POST" class="mt-4" onsubmit="return confirm('Deliver to the customer? They are emailed and the final invoice goes out.');">
                                @csrf
                                <button type="submit" class="{{ $primary }}">{{ $order->hasStatus('delivered') ? 'Deliver the new version' : 'Deliver for review' }}</button>
                                <span class="ml-2 text-xs text-gray-500">Upload the draft or final file first. Open revisions are marked as done.</span>
                            </form>
                        @endif

                        <form action="{{ route('services.orders.charges.store', $order) }}" method="POST" class="mt-4 flex flex-wrap items-end gap-3 border-t border-gray-100 pt-4">
                            @csrf
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Extra charge (e.g. extra revision)</label>
                                <input type="text" name="label" maxlength="255" required placeholder="Description" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Amount (Rp)</label>
                                <input type="number" name="amount" min="1" step="1" required class="mt-1 w-36 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                            </div>
                            <button type="submit" class="{{ $secondary }}">Add &amp; invoice</button>
                        </form>
                    @endif
                @endcan
            </div>
        @endif

        @can('sales.manage')
            @if ($order->isOpen())
                <form action="{{ route('services.orders.cancel', $order) }}" method="POST" onsubmit="return confirm('Cancel this order? Unpaid invoices are removed; recorded payments stay in Finance.');">
                    @csrf
                    <button type="submit" class="text-sm text-gray-500 hover:text-red-600">Cancel this order</button>
                </form>
            @endif
        @endcan

        <a href="{{ route('services.orders.index') }}" class="inline-block text-sm text-gray-500 hover:text-gray-700">&larr; All orders</a>
    </div>
</x-layouts.erp>
